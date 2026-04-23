<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EmployeeController extends Controller
{
    public function index()
    {
        $employees = Employee::with(['supervisor:id,first_name,last_name,position', 'subordinates:id,supervisor_id'])
            ->latest()
            ->get();

        return response()->json(['data' => $employees]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateRequest($request);
        $employee = Employee::create($validated)->load(['supervisor:id,first_name,last_name,position', 'subordinates:id,supervisor_id']);

        return response()->json(['data' => $employee, 'message' => 'Employee created successfully.'], 201);
    }

    public function show(Employee $employee)
    {
        return response()->json([
            'data' => $employee->load([
                'supervisor:id,first_name,last_name,position',
                'subordinates:id,supervisor_id,first_name,last_name,position,status',
                'technicianLogs' => fn ($query) => $query->latest('log_date'),
            ]),
        ]);
    }

    public function update(Request $request, Employee $employee)
    {
        $validated = $this->validateRequest($request, $employee->id);
        $employee->update($validated);

        return response()->json([
            'data' => $employee->fresh(['supervisor:id,first_name,last_name,position', 'subordinates:id,supervisor_id']),
            'message' => 'Employee updated successfully.',
        ]);
    }

    public function destroy(Employee $employee)
    {
        $employee->delete();

        return response()->json(['message' => 'Employee deleted successfully.']);
    }

    public function orgChart()
    {
        $employees = Employee::orderBy('last_name')->orderBy('first_name')->get();
        $byParent = $employees->groupBy(fn (Employee $employee) => $employee->supervisor_id ?: 0);

        $buildTree = function ($parentId) use (&$buildTree, $byParent) {
            return collect($byParent[$parentId] ?? [])->map(function (Employee $employee) use (&$buildTree) {
                return [
                    'id' => $employee->id,
                    'name' => trim("{$employee->first_name} {$employee->last_name}"),
                    'position' => $employee->position,
                    'department' => $employee->department,
                    'division' => $employee->division,
                    'status' => $employee->status,
                    'children' => $buildTree($employee->id)->values()->all(),
                ];
            })->values();
        };

        return response()->json(['data' => $buildTree(0)->all()]);
    }

    private function validateRequest(Request $request, ?int $employeeId = null): array
    {
        return $request->validate([
            'employee_no' => ['required', 'string', 'max:255', Rule::unique('employees', 'employee_no')->ignore($employeeId)],
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'suffix' => 'nullable|string|max:255',
            'email' => ['nullable', 'email', 'max:255', Rule::unique('employees', 'email')->ignore($employeeId)],
            'contact_no' => 'nullable|string|max:255',
            'position' => 'required|string|max:255',
            'department' => 'required|string|max:255',
            'division' => 'nullable|string|max:255',
            'employment_type' => 'required|string|max:255',
            'status' => ['required', Rule::in(['Active', 'Inactive', 'On Leave'])],
            'supervisor_id' => 'nullable|exists:employees,id',
            'work_location' => 'nullable|string|max:255',
            'current_assignment' => 'nullable|string|max:255',
            'face_reference_image' => 'nullable|string',
        ]);
    }
}
