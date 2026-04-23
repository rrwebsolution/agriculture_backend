<?php

namespace App\Http\Controllers\Api;

use App\Exports\ReportExport;
use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\Farmer;
use App\Models\Fisherfolk;
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
    private ?Report $currentReport = null;
    private array $currentReportFilters = [];

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
            'status' => ['required', Rule::in(['Published', 'Pending Review', 'Draft'])],
            'notes' => 'nullable|string|max:2000',
            'filters' => 'nullable|array',
            'selected_fields' => 'nullable|array|min:1',
            'selected_fields.*' => 'string|max:255',
        ]);

        $validated['generated_by'] = Auth::user()->name ?? 'System';
        $validated['generated_at'] = now();
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
        $this->currentReport = $report;
        $this->currentReportFilters = $this->normalizeFilters($report->filters);

        return match ($report->type) {
            default => $this->fetchByModule($report),
            'Production' => $this->fetchProduction($report->period_from, $report->period_to),
            'Fishery' => $this->fetchFishery($report->period_from, $report->period_to),
            'Livestock & Poultry' => $this->fetchLivestock(),
            'Financial' => $this->fetchFinancial($report->period_from, $report->period_to),
            'Census' => $this->fetchCensus(),
            'Inventory' => $this->fetchInventory(),
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
        $filters = $this->currentReportFilters;
        $harvests = Harvest::with(['farmer', 'barangay', 'crop'])
            ->whereBetween('dateHarvested', [$from, $to])
            ->when(isset($filters['barangay_id']), fn ($query) => $query->where('barangay_id', $filters['barangay_id']))
            ->when(isset($filters['crop_id']), fn ($query) => $query->where('crop_id', $filters['crop_id']))
            ->when(isset($filters['farmer_id']), fn ($query) => $query->where('farmer_id', $filters['farmer_id']))
            ->when(isset($filters['quality']), fn ($query) => $query->where('quality', $filters['quality']))
            ->orderBy('dateHarvested')
            ->get();

        $availableFields = [
            'farmer' => 'Farmer',
            'crop' => 'Crop',
            'barangay' => 'Barangay',
            'date_harvested' => 'Date Harvested',
            'quantity' => 'Quantity',
            'quality' => 'Quality',
            'value' => 'Value (PHP)',
        ];

        return $this->buildRows(
            $availableFields,
            $harvests,
            fn ($h, $field) => match ($field) {
                'farmer' => $this->textValue(trim(($h->farmer->first_name ?? '') . ' ' . ($h->farmer->last_name ?? '')), 'Unknown Farmer'),
                'crop' => $this->textValue($h->crop->name ?? $h->crop->category ?? null, 'Unknown Crop'),
                'barangay' => $this->textValue($h->barangay->name ?? null, 'Unknown Barangay'),
                'date_harvested' => $this->dateValue($h->dateHarvested, 'Unknown Date'),
                'quantity' => $this->measurementValue($h->quantity),
                'quality' => $this->textValue($h->quality, 'Unspecified'),
                'value' => $this->numberValue($h->value),
                default => '',
            }
        );
    }

    private function fetchFishery($from, $to): array
    {
        $filters = $this->currentReportFilters;
        $records = FisheryRecord::whereBetween('date', [$from, $to])
            ->when(isset($filters['fishr_id']), fn ($query) => $query->where('fishr_id', $filters['fishr_id']))
            ->when(isset($filters['gear_type']), fn ($query) => $query->where('gear_type', $filters['gear_type']))
            ->when(isset($filters['fishing_area']), fn ($query) => $query->where('fishing_area', 'like', '%' . $filters['fishing_area'] . '%'))
            ->orderBy('date')
            ->get();

        $availableFields = [
            'name' => 'Name',
            'boat_name' => 'Boat',
            'gear_type' => 'Gear Type',
            'fishing_area' => 'Fishing Area',
            'catch_species' => 'Species',
            'yield' => 'Yield (kg)',
            'market_value' => 'Market Value (PHP)',
            'hours_spent_fishing' => 'Hours Spent Fishing',
            'date' => 'Date',
        ];

        return $this->buildRows(
            $availableFields,
            $records,
            fn ($r, $field) => match ($field) {
                'name' => $this->textValue($r->name, 'Unknown Fisherfolk'),
                'boat_name' => $this->textValue($r->boat_name, 'No Boat Listed'),
                'gear_type' => $this->textValue($r->gear_type, 'Unspecified'),
                'fishing_area' => $this->textValue($r->fishing_area, 'Unspecified'),
                'catch_species' => $this->textValue($r->catch_species, 'Unspecified'),
                'yield' => $this->measurementValue($r->yield, 'kg', '0.00 kg'),
                'market_value' => $this->numberValue($r->market_value),
                'hours_spent_fishing' => $this->measurementValue($r->hours_spent_fishing, 'hrs', '0.00 hrs'),
                'date' => $this->dateValue($r->date, 'Unknown Date'),
                default => '',
            }
        );
    }

    private function fetchLivestock(): array
    {
        return [
            'headers' => ['Note'],
            'rows' => [['Livestock & Poultry module data is not yet available.']],
        ];
    }

    private function fetchFinancial($from, $to): array
    {
        $filters = $this->currentReportFilters;
        $expenses = Expense::whereBetween('date_incurred', [$from, $to])
            ->when(isset($filters['category']), fn ($query) => $query->where('category', $filters['category']))
            ->when(isset($filters['status']), fn ($query) => $query->where('status', $filters['status']))
            ->when(isset($filters['project']), fn ($query) => $query->where('project', 'like', '%' . $filters['project'] . '%'))
            ->orderBy('date_incurred')
            ->get();

        $availableFields = [
            'ref_no' => 'Ref No.',
            'item' => 'Item',
            'category' => 'Category',
            'project' => 'Project',
            'amount' => 'Amount (PHP)',
            'date_incurred' => 'Date',
            'status' => 'Status',
            'remarks' => 'Remarks',
        ];

        $data = $this->buildRows(
            $availableFields,
            $expenses,
            fn ($e, $field) => match ($field) {
                'ref_no' => $this->textValue($e->ref_no, 'No Reference'),
                'item' => $this->textValue($e->item, 'Unnamed Expense'),
                'category' => $this->textValue($e->category, 'Uncategorized'),
                'project' => $this->textValue($e->project, 'Unassigned Project'),
                'amount' => $this->numberValue($e->amount),
                'date_incurred' => $this->dateValue($e->date_incurred, 'Unknown Date'),
                'status' => $this->textValue($e->status, 'Unspecified'),
                'remarks' => $this->textValue($e->remarks, 'No Remarks'),
                default => '',
            }
        );

        $data['total'] = $expenses->sum('amount');

        return $data;
    }

    private function fetchCensus(): array
    {
        if ($this->currentReport?->module === 'Fisherfolk Registry') {
            return $this->fetchFisherfolkRegistry();
        }

        $filters = $this->currentReportFilters;
        $farmers = Farmer::with(['barangay', 'crop'])
            ->when(isset($filters['barangay_id']), fn ($query) => $query->where('barangay_id', $filters['barangay_id']))
            ->when(isset($filters['crop_id']), fn ($query) => $query->where('crop_id', $filters['crop_id']))
            ->when(isset($filters['gender']), fn ($query) => $query->where('gender', $filters['gender']))
            ->when(isset($filters['status']), fn ($query) => $query->where('status', $filters['status']))
            ->when(isset($filters['is_main_livelihood']), fn ($query) => $query->where('is_main_livelihood', filter_var($filters['is_main_livelihood'], FILTER_VALIDATE_BOOLEAN)))
            ->orderBy('last_name')
            ->get();

        $availableFields = [
            'full_name' => 'Full Name',
            'gender' => 'Sex',
            'barangay' => 'Barangay',
            'contact_no' => 'Contact No.',
            'primary_crop' => 'Primary Crop',
            'farm_area' => 'Farm Area (ha)',
            'ownership' => 'Ownership',
            'soil_type' => 'Soil Type',
            'status' => 'Status',
        ];

        $data = $this->buildRows(
            $availableFields,
            $farmers,
            fn ($f, $field) => match ($field) {
                'full_name' => $this->textValue(trim("{$f->last_name}, {$f->first_name}" . ($f->middle_name ? ' ' . $f->middle_name[0] . '.' : '')), 'Unknown Farmer'),
                'gender' => $this->textValue($f->gender, 'Unspecified'),
                'barangay' => $this->textValue($f->barangay->name ?? null, 'Unknown Barangay'),
                'contact_no' => $this->textValue($f->contact_no, 'No Contact'),
                'primary_crop' => $this->textValue($f->crop->name ?? $f->crop->category ?? null, 'Unknown Crop'),
                'farm_area' => $this->numberValue($f->total_area),
                'ownership' => $this->textValue($f->ownership_type, 'Unspecified'),
                'soil_type' => $this->textValue($f->soil_type, 'Unspecified'),
                'status' => $this->textValue($f->status, 'Unspecified'),
                default => '',
            }
        );

        $data['summary'] = $this->sexSummary($farmers);

        return $data;
    }

    private function fetchFisherfolkRegistry(): array
    {
        $filters = $this->currentReportFilters;
        $fisherfolks = Fisherfolk::with('barangay')
            ->when(isset($filters['barangay_id']), fn ($query) => $query->where('barangay_id', $filters['barangay_id']))
            ->when(isset($filters['gender']), fn ($query) => $query->where('gender', $filters['gender']))
            ->when(isset($filters['status']), fn ($query) => $query->where('status', $filters['status']))
            ->when(isset($filters['fisher_type']), fn ($query) => $query->where('fisher_type', 'like', '%' . $filters['fisher_type'] . '%'))
            ->orderBy('last_name')
            ->get();

        $availableFields = [
            'full_name' => 'Full Name',
            'gender' => 'Sex',
            'barangay' => 'Barangay',
            'contact_no' => 'Contact No.',
            'fisher_type' => 'Fisher Type',
            'years_in_fishing' => 'Years in Fishing',
            'status' => 'Status',
        ];

        $data = $this->buildRows(
            $availableFields,
            $fisherfolks,
            fn ($f, $field) => match ($field) {
                'full_name' => $this->textValue(trim("{$f->last_name}, {$f->first_name}" . ($f->middle_name ? ' ' . $f->middle_name[0] . '.' : '')), 'Unknown Fisherfolk'),
                'gender' => $this->textValue($f->gender, 'Unspecified'),
                'barangay' => $this->textValue($f->barangay->name ?? null, 'Unknown Barangay'),
                'contact_no' => $this->textValue($f->contact_no, 'No Contact'),
                'fisher_type' => $this->textValue($f->fisher_type, 'Unspecified'),
                'years_in_fishing' => $this->measurementValue($f->years_in_fishing, 'yrs', '0.00 yrs'),
                'status' => $this->textValue($f->status, 'Unspecified'),
                default => '',
            }
        );

        $data['summary'] = $this->sexSummary($fisherfolks);

        return $data;
    }

    private function fetchInventory(): array
    {
        $filters = $this->currentReportFilters;
        $items = Inventory::orderBy('name')
            ->when(isset($filters['category']), fn ($query) => $query->where('category', $filters['category']))
            ->when(isset($filters['status']), fn ($query) => $query->where('status', $filters['status']))
            ->when(isset($filters['commodity']), fn ($query) => $query->where('commodity', 'like', '%' . $filters['commodity'] . '%'))
            ->when(isset($filters['year']), fn ($query) => $query->where('year', $filters['year']))
            ->get();

        $availableFields = [
            'name' => 'Item Name',
            'commodity' => 'Commodity',
            'category' => 'Category',
            'sku' => 'SKU',
            'stock' => 'Stock',
            'unit' => 'Unit',
            'status' => 'Status',
            'year' => 'Year',
        ];

        return $this->buildRows(
            $availableFields,
            $items,
            fn ($i, $field) => match ($field) {
                'name' => $this->textValue($i->name, 'Unnamed Item'),
                'commodity' => $this->textValue($i->commodity, 'Unspecified'),
                'category' => $this->textValue($i->category, 'Uncategorized'),
                'sku' => $this->textValue($i->sku, 'No SKU'),
                'stock' => $this->numberValue($i->stock),
                'unit' => $this->textValue($i->unit, 'Unspecified'),
                'status' => $this->textValue($i->status, 'Unspecified'),
                'year' => $this->textValue($i->year, 'Unknown Year'),
                default => '',
            }
        );
    }

    private function normalizeFilters(?array $filters): array
    {
        return collect($filters ?? [])
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->all();
    }

    private function selectedFields(array $availableFields): array
    {
        if (!$this->currentReport || empty($this->currentReport->selected_fields)) {
            return array_keys($availableFields);
        }

        $selected = array_values(array_filter(
            $this->currentReport->selected_fields,
            fn ($field) => array_key_exists($field, $availableFields)
        ));

        return empty($selected) ? array_keys($availableFields) : $selected;
    }

    private function buildRows(array $availableFields, iterable $items, callable $resolver): array
    {
        $selectedFields = $this->selectedFields($availableFields);
        $headers = array_map(fn ($field) => $availableFields[$field], $selectedFields);
        $rows = collect($items)->map(function ($item) use ($selectedFields, $resolver) {
            return array_map(fn ($field) => $resolver($item, $field), $selectedFields);
        })->toArray();

        return compact('headers', 'rows');
    }

    private function fetchByModule(Report $report): array
    {
        return match ($report->module) {
            'Farmer Registry' => $this->fetchCensus(),
            'Fisherfolk Registry' => $this->fetchFisherfolkRegistry(),
            default => ['headers' => [], 'rows' => []],
        };
    }

    private function sexSummary(iterable $items): array
    {
        $collection = collect($items);

        return [
            'male_count' => $collection->filter(fn ($item) => strcasecmp((string) ($item->gender ?? ''), 'Male') === 0)->count(),
            'female_count' => $collection->filter(fn ($item) => strcasecmp((string) ($item->gender ?? ''), 'Female') === 0)->count(),
        ];
    }
}
