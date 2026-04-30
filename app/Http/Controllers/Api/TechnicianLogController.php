<?php

namespace App\Http\Controllers\Api;

use App\Events\TechnicianLogUpdated;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\TechnicianLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

class TechnicianLogController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user()?->loadMissing('role');

        $query = TechnicianLog::with('employee:id,employee_no,first_name,last_name,position,division,department,work_location,email')
            ->latest();

        if (!$this->isAdminUser($user)) {
            $employee = $this->resolveEmployeeForUser($user);

            if (!$employee) {
                return response()->json(['data' => []]);
            }

            $query->where('employee_id', $employee->id);
        }

        $logs = $query->get();

        return response()->json(['data' => $logs]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateRequest($request);
        $validated = $this->enforceEmployeeScope($request, $validated);
        $this->enforceFaceVerification($validated);
        $this->preventDuplicateMovement($validated);
        $log = TechnicianLog::create($validated)->load('employee:id,employee_no,first_name,last_name,position,division,department,work_location,email');
        $this->broadcastTechnicianLogUpdate($log, 'created');

        return response()->json(['data' => $log, 'message' => 'Technician log created successfully.'], 201);
    }

    public function show(Request $request, TechnicianLog $technicianLog)
    {
        $this->authorizeLogAccess($request->user()?->loadMissing('role'), $technicianLog);

        return response()->json(['data' => $technicianLog->load('employee:id,employee_no,first_name,last_name,position,division,department,work_location,email')]);
    }

    public function update(Request $request, TechnicianLog $technicianLog)
    {
        $user = $request->user()?->loadMissing('role');
        $this->authorizeLogAccess($user, $technicianLog);

        $validated = $this->validateRequest($request, $technicianLog);
        $validated = $this->enforceEmployeeScope($request, $validated, $technicianLog);
        $this->enforceFaceVerification($validated);
        $this->preventDuplicateMovement($validated, $technicianLog->id);
        $technicianLog->update($validated);
        $freshLog = $technicianLog->fresh('employee:id,employee_no,first_name,last_name,position,division,department,work_location,email');
        $this->broadcastTechnicianLogUpdate($freshLog, 'updated');

        return response()->json([
            'data' => $freshLog,
            'message' => 'Technician log updated successfully.',
        ]);
    }

    public function destroy(Request $request, TechnicianLog $technicianLog)
    {
        $user = $request->user()?->loadMissing('role');
        $isOwner = (int) $technicianLog->employee_id === (int) $this->resolveEmployeeForUser($user)?->id;

        if (!$this->isAdminUser($user) && !($this->canManageTechnicianLogs($user) && $isOwner)) {
            throw new AuthorizationException('You are not allowed to delete this employee log.');
        }

        $snapshot = $technicianLog
            ->load('employee:id,employee_no,first_name,last_name,position,division,department,work_location,email')
            ->toArray();

        $technicianLog->delete();
        $this->broadcastTechnicianLogUpdate($snapshot, 'deleted');

        return response()->json(['message' => 'Technician log deleted successfully.']);
    }

    private function broadcastTechnicianLogUpdate(TechnicianLog|array $technicianLog, string $type): void
    {
        try {
            broadcast(new TechnicianLogUpdated($technicianLog, $type))->toOthers();
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    private function validateRequest(Request $request, ?TechnicianLog $existingLog = null): array
    {
        $isUpdate = $existingLog !== null;

        return $request->validate([
            'employee_id' => [$isUpdate ? 'sometimes' : 'required', 'exists:employees,id'],
            'log_date' => [$isUpdate ? 'sometimes' : 'required', 'date'],
            'location_name' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:255'],
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'assignment' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:255'],
            'status' => [$isUpdate ? 'sometimes' : 'required', Rule::in(['Planned', 'Deployed', 'In Field', 'Completed'])],
            'notes' => 'nullable|string',
            'face_verified' => 'nullable|boolean',
            'face_verified_at' => 'nullable|date',
            'face_match_score' => 'nullable|numeric|min:0|max:100',
            'verification_photo' => 'nullable|string',
        ]) + ($existingLog ? [
            'employee_id' => $request->input('employee_id', $existingLog->employee_id),
            'log_date' => $request->input('log_date', optional($existingLog->log_date)->format('Y-m-d')),
            'location_name' => $request->input('location_name', $existingLog->location_name),
            'latitude' => $request->input('latitude', $existingLog->latitude),
            'longitude' => $request->input('longitude', $existingLog->longitude),
            'assignment' => $request->input('assignment', $existingLog->assignment),
            'status' => $request->input('status', $existingLog->status),
            'notes' => $request->input('notes', $existingLog->notes),
            'face_verified' => $request->has('face_verified') ? $request->input('face_verified') : $existingLog->face_verified,
            'face_verified_at' => $request->input('face_verified_at', $existingLog->face_verified_at?->format('Y-m-d H:i:s')),
            'face_match_score' => $request->input('face_match_score', $existingLog->face_match_score),
            'verification_photo' => $request->input('verification_photo', $existingLog->verification_photo),
        ] : []);
    }

    private function enforceEmployeeScope(Request $request, array $validated, ?TechnicianLog $existingLog = null): array
    {
        $user = $request->user()?->loadMissing('role');

        if ($this->isAdminUser($user)) {
            return $validated;
        }

        $employee = $this->resolveEmployeeForUser($user);

        if (!$employee) {
            throw ValidationException::withMessages([
                'employee_id' => 'No employee profile is linked to your account.',
            ]);
        }

        $targetEmployeeId = (int) ($validated['employee_id'] ?? $existingLog?->employee_id ?? 0);

        if ($targetEmployeeId !== (int) $employee->id) {
            throw ValidationException::withMessages([
                'employee_id' => 'You can only create or update your own employee movement logs.',
            ]);
        }

        $validated['employee_id'] = $employee->id;

        return $validated;
    }

    private function authorizeLogAccess(?User $user, TechnicianLog $technicianLog): void
    {
        if ($this->isAdminUser($user)) {
            return;
        }

        $employee = $this->resolveEmployeeForUser($user);

        if (!$employee || (int) $technicianLog->employee_id !== (int) $employee->id) {
            throw new AuthorizationException('You are not allowed to access this employee log.');
        }
    }

    private function resolveEmployeeForUser(?User $user): ?Employee
    {
        $email = strtolower((string) $user?->email);

        if ($email === '') {
            return null;
        }

        return Employee::whereRaw('LOWER(email) = ?', [$email])->first();
    }

    private function isAdminUser(?User $user): bool
    {
        return in_array($user?->role?->name, ['Administrator', 'System Administrator', 'pageistrator'], true);
    }

    private function canManageTechnicianLogs(?User $user): bool
    {
        $permissions = $user?->role?->permissions;

        if (!is_array($permissions)) {
            return false;
        }

        return in_array('Administration: Manage Technician Logs', $permissions, true);
    }

    private function preventDuplicateMovement(array $validated, ?int $ignoreId = null): void
    {
        $duplicateWindowMinutes = 5;

        $duplicateQuery = TechnicianLog::query()
            ->where('employee_id', $validated['employee_id'])
            ->whereDate('log_date', $validated['log_date'])
            ->where('location_name', $validated['location_name'])
            ->where('assignment', $validated['assignment'])
            ->where('status', $validated['status'])
            ->where('created_at', '>=', now()->subMinutes($duplicateWindowMinutes));

        if ($ignoreId) {
            $duplicateQuery->where('id', '!=', $ignoreId);
        }

        if ($duplicateQuery->exists()) {
            throw ValidationException::withMessages([
                'duplicate_log' => 'A similar technician movement log was already recorded within the last 5 minutes. Please try again after 5 minutes.',
            ]);
        }
    }

    private function enforceFaceVerification(array $validated): void
    {
        $employee = Employee::find($validated['employee_id']);

        if ($employee?->face_reference_image && empty($validated['face_verified'])) {
            throw ValidationException::withMessages([
                'face_verified' => 'Face verification is required for this technician before movement logging.',
            ]);
        }
    }
}
