<?php

namespace App\Http\Controllers\Api;

use App\Exports\ReportExport;
use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\Farmer;
use App\Models\FisheryRecord;
use App\Models\Harvest;
use App\Models\Inventory;
use App\Models\Report;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    public function index()
    {
        $reports = Report::latest('generated_at')->get();

        return response()->json([
            'reports' => $reports,
            'message' => 'Reports fetched successfully.',
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'type' => ['required', Rule::in(['Production', 'Fishery', 'Livestock & Poultry', 'Financial', 'Census', 'Inventory'])],
            'module' => 'required|string|max:255',
            'period_from' => 'required|date',
            'period_to' => 'required|date|after_or_equal:period_from',
            'format' => ['required', Rule::in(['PDF', 'XLSX'])],
            'status' => ['nullable', Rule::in(['Published', 'Pending Review', 'Draft'])],
            'notes' => 'nullable|string|max:2000',
        ]);

        $validated['generated_by'] = Auth::user()->name ?? 'System';
        $validated['generated_at'] = now();
        $validated['status'] = $validated['status'] ?? 'Published';

        $report = Report::create($validated);

        try {
            $filePath = $this->generateFile($report);
            $report->update(['file_path' => $filePath]);
        } catch (\Exception $e) {
            \Log::error("Report file generation failed for ID {$report->id}: " . $e->getMessage());
        }

        return response()->json([
            'data' => $report->fresh(),
            'message' => 'Report generated successfully.',
        ], 201);
    }

    public function download(Report $report)
    {
        try {
            $filePath = $this->generateFile($report);
            $report->update(['file_path' => $filePath]);
            $report->refresh();
        } catch (\Exception $e) {
            \Log::error("Report regeneration failed for ID {$report->id}: " . $e->getMessage());
        }

        if (!$report->file_path || !Storage::exists($report->file_path)) {
            return response()->json([
                'message' => 'No file available for this report yet.',
            ], 404);
        }

        $mime = $report->format === 'PDF'
            ? 'application/pdf'
            : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

        $ext = strtolower($report->format);
        $filename = "{$report->title}.{$ext}";

        return Storage::download($report->file_path, $filename, [
            'Content-Type' => $mime,
        ]);
    }

    public function destroy(Report $report)
    {
        if ($report->file_path && Storage::exists($report->file_path)) {
            Storage::delete($report->file_path);
        }

        $report->delete();

        return response()->json([
            'message' => 'Report deleted successfully.',
        ]);
    }

    private function generateFile(Report $report): string
    {
        $data = $this->fetchReportData($report);
        $safeName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $report->type);
        $filename = "{$report->id}_{$safeName}";

        Storage::makeDirectory('reports');

        if ($report->format === 'PDF') {
            $pdf = Pdf::loadView('reports.template', compact('report', 'data'))
                ->setPaper('a4', 'landscape');
            $path = "reports/{$filename}.pdf";
            Storage::put($path, $pdf->output());
        } else {
            $path = "reports/{$filename}.xlsx";
            Excel::store(new ReportExport($report, $data), $path);
        }

        return $path;
    }

    private function fetchReportData(Report $report): array
    {
        return match ($report->type) {
            'Production' => $this->fetchProduction($report->period_from, $report->period_to),
            'Fishery' => $this->fetchFishery($report->period_from, $report->period_to),
            'Livestock & Poultry' => $this->fetchLivestock(),
            'Financial' => $this->fetchFinancial($report->period_from, $report->period_to),
            'Census' => $this->fetchCensus(),
            'Inventory' => $this->fetchInventory(),
            default => ['headers' => [], 'rows' => []],
        };
    }

    private function textValue(mixed $value, string $fallback = 'Not Available'): string
    {
        $normalized = is_string($value) ? trim($value) : $value;

        if ($normalized === null || $normalized === '') {
            return $fallback;
        }

        return (string) $normalized;
    }

    private function numberValue(mixed $value, int $decimals = 2): string
    {
        return number_format((float) ($value ?? 0), $decimals);
    }

    private function measurementValue(mixed $value, ?string $defaultUnit = null, string $fallback = 'Not Available', int $decimals = 2): string
    {
        $normalized = is_string($value) ? trim($value) : $value;

        if ($normalized === null || $normalized === '') {
            return $fallback;
        }

        if (is_string($normalized) && preg_match('/[A-Za-z]/', $normalized)) {
            return $normalized;
        }

        $formatted = number_format((float) $normalized, $decimals);

        return $defaultUnit ? "{$formatted} {$defaultUnit}" : $formatted;
    }

    private function dateValue(mixed $value, string $fallback = 'Not Available'): string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        return $this->textValue($value, $fallback);
    }

    private function fetchProduction($from, $to): array
    {
        $harvests = Harvest::with(['farmer', 'barangay', 'crop'])
            ->whereBetween('dateHarvested', [$from, $to])
            ->orderBy('dateHarvested')
            ->get();

        $headers = ['Farmer', 'Crop', 'Barangay', 'Date Harvested', 'Quantity', 'Quality', 'Value (PHP)'];
        $rows = $harvests->map(fn ($h) => [
            $this->textValue(trim(($h->farmer->first_name ?? '') . ' ' . ($h->farmer->last_name ?? '')), 'Unknown Farmer'),
            $this->textValue($h->crop->name ?? $h->crop->category ?? null, 'Unknown Crop'),
            $this->textValue($h->barangay->name ?? null, 'Unknown Barangay'),
            $this->dateValue($h->dateHarvested, 'Unknown Date'),
            $this->measurementValue($h->quantity),
            $this->textValue($h->quality, 'Unspecified'),
            $this->numberValue($h->value),
        ])->toArray();

        return compact('headers', 'rows');
    }

    private function fetchFishery($from, $to): array
    {
        $records = FisheryRecord::whereBetween('date', [$from, $to])
            ->orderBy('date')
            ->get();

        $headers = ['Name', 'Boat', 'Gear Type', 'Fishing Area', 'Species', 'Yield (kg)', 'Market Value (PHP)', 'Date'];
        $rows = $records->map(fn ($r) => [
            $this->textValue($r->name, 'Unknown Fisherfolk'),
            $this->textValue($r->boat_name, 'No Boat Listed'),
            $this->textValue($r->gear_type, 'Unspecified'),
            $this->textValue($r->fishing_area, 'Unspecified'),
            $this->textValue($r->catch_species, 'Unspecified'),
            $this->measurementValue($r->yield, 'kg', '0.00 kg'),
            $this->numberValue($r->market_value),
            $this->dateValue($r->date, 'Unknown Date'),
        ])->toArray();

        return compact('headers', 'rows');
    }

    private function fetchLivestock(): array
    {
        $headers = ['Note'];
        $rows = [['Livestock & Poultry module data is not yet available.']];

        return compact('headers', 'rows');
    }

    private function fetchFinancial($from, $to): array
    {
        $expenses = Expense::whereBetween('date_incurred', [$from, $to])
            ->orderBy('date_incurred')
            ->get();

        $headers = ['Ref No.', 'Item', 'Category', 'Project', 'Amount (PHP)', 'Date', 'Status', 'Remarks'];
        $rows = $expenses->map(fn ($e) => [
            $this->textValue($e->ref_no, 'No Reference'),
            $this->textValue($e->item, 'Unnamed Expense'),
            $this->textValue($e->category, 'Uncategorized'),
            $this->textValue($e->project, 'Unassigned Project'),
            $this->numberValue($e->amount),
            $this->dateValue($e->date_incurred, 'Unknown Date'),
            $this->textValue($e->status, 'Unspecified'),
            $this->textValue($e->remarks, 'No Remarks'),
        ])->toArray();

        $total = $expenses->sum('amount');

        return compact('headers', 'rows', 'total');
    }

    private function fetchCensus(): array
    {
        $farmers = Farmer::with(['barangay', 'crop'])
            ->orderBy('last_name')
            ->get();

        $headers = ['Full Name', 'Gender', 'Barangay', 'Contact No.', 'Primary Crop', 'Farm Area (ha)', 'Ownership', 'Status'];
        $rows = $farmers->map(fn ($f) => [
            $this->textValue(trim("{$f->last_name}, {$f->first_name}" . ($f->middle_name ? ' ' . $f->middle_name[0] . '.' : '')), 'Unknown Farmer'),
            $this->textValue($f->gender, 'Unspecified'),
            $this->textValue($f->barangay->name ?? null, 'Unknown Barangay'),
            $this->textValue($f->contact_no, 'No Contact'),
            $this->textValue($f->crop->name ?? $f->crop->category ?? null, 'Unknown Crop'),
            $this->numberValue($f->total_area),
            $this->textValue($f->ownership_type, 'Unspecified'),
            $this->textValue($f->status, 'Unspecified'),
        ])->toArray();

        return compact('headers', 'rows');
    }

    private function fetchInventory(): array
    {
        $items = Inventory::orderBy('name')->get();

        $headers = ['Item Name', 'Commodity', 'Category', 'SKU', 'Stock', 'Unit', 'Status', 'Year'];
        $rows = $items->map(fn ($i) => [
            $this->textValue($i->name, 'Unnamed Item'),
            $this->textValue($i->commodity, 'Unspecified'),
            $this->textValue($i->category, 'Uncategorized'),
            $this->textValue($i->sku, 'No SKU'),
            $this->numberValue($i->stock),
            $this->textValue($i->unit, 'Unspecified'),
            $this->textValue($i->status, 'Unspecified'),
            $this->textValue($i->year, 'Unknown Year'),
        ])->toArray();

        return compact('headers', 'rows');
    }
}
