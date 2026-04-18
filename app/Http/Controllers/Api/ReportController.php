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
    // ─────────────────────────────────────────────
    // GET /api/reports
    // ─────────────────────────────────────────────
    public function index()
    {
        $reports = Report::latest('generated_at')->get();

        return response()->json([
            'reports' => $reports,
            'message' => 'Reports fetched successfully.',
        ]);
    }

    // ─────────────────────────────────────────────
    // POST /api/reports
    // Creates record then generates the actual file.
    // ─────────────────────────────────────────────
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
        ]);

        $validated['generated_by'] = Auth::user()->name ?? 'System';
        $validated['generated_at'] = now();

        $report = Report::create($validated);

        try {
            $filePath = $this->generateFile($report);
            $report->update(['file_path' => $filePath]);
        } catch (\Exception $e) {
            \Log::error("Report file generation failed for ID {$report->id}: ".$e->getMessage());
        }

        return response()->json([
            'data' => $report->fresh(),
            'message' => 'Report generated successfully.',
        ], 201);
    }

    // ─────────────────────────────────────────────
    // GET /api/reports/{id}/download
    // ─────────────────────────────────────────────
    public function download(Report $report)
    {
        if (! $report->file_path || ! Storage::exists($report->file_path)) {
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

    // ─────────────────────────────────────────────
    // DELETE /api/reports/{id}
    // ─────────────────────────────────────────────
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

    // ─────────────────────────────────────────────
    // PRIVATE: Generate PDF or XLSX, return storage path
    // ─────────────────────────────────────────────
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

    // ─────────────────────────────────────────────
    // PRIVATE: Fetch rows for each report type
    // ─────────────────────────────────────────────
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

    private function fetchProduction($from, $to): array
    {
        $harvests = Harvest::with(['farmer', 'barangay', 'crop'])
            ->whereBetween('dateHarvested', [$from, $to])
            ->orderBy('dateHarvested')
            ->get();

        $headers = ['Farmer', 'Crop', 'Barangay', 'Date Harvested', 'Quantity (kg)', 'Quality', 'Value (₱)'];
        $rows = $harvests->map(fn ($h) => [
            trim(($h->farmer->first_name ?? '').' '.($h->farmer->last_name ?? '')) ?: '—',
            $h->crop->name ?? '—',
            $h->barangay->name ?? '—',
            $h->dateHarvested,
            $h->quantity,
            $h->quality ?? '—',
            number_format((float) $h->value, 2),
        ])->toArray();

        return compact('headers', 'rows');
    }

    private function fetchFishery($from, $to): array
    {
        $records = FisheryRecord::whereBetween('date', [$from, $to])
            ->orderBy('date')
            ->get();

        $headers = ['Name', 'Boat', 'Gear Type', 'Fishing Area', 'Species', 'Yield (kg)', 'Market Value (₱)', 'Date'];
        $rows = $records->map(fn ($r) => [
            $r->name ?? '—',
            $r->boat_name ?? '—',
            $r->gear_type ?? '—',
            $r->fishing_area ?? '—',
            $r->catch_species ?? '—',
            $r->yield,
            number_format((float) $r->market_value, 2),
            $r->date,
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

        $headers = ['Ref No.', 'Item', 'Category', 'Project', 'Amount (₱)', 'Date', 'Status', 'Remarks'];
        $rows = $expenses->map(fn ($e) => [
            $e->ref_no ?? '—',
            $e->item ?? '—',
            $e->category ?? '—',
            $e->project ?? '—',
            number_format((float) $e->amount, 2),
            $e->date_incurred?->format('Y-m-d'),
            $e->status ?? '—',
            $e->remarks ?? '—',
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
            trim("{$f->last_name}, {$f->first_name}".($f->middle_name ? ' '.$f->middle_name[0].'.' : '')),
            $f->gender ?? '—',
            $f->barangay->name ?? '—',
            $f->contact_no ?? '—',
            $f->crop->name ?? '—',
            $f->total_area ?? '—',
            $f->ownership_type ?? '—',
            $f->status ?? '—',
        ])->toArray();

        return compact('headers', 'rows');
    }

    private function fetchInventory(): array
    {
        $items = Inventory::orderBy('name')->get();

        $headers = ['Item Name', 'Commodity', 'Category', 'SKU', 'Stock', 'Unit', 'Status', 'Year'];
        $rows = $items->map(fn ($i) => [
            $i->name ?? '—',
            $i->commodity ?? '—',
            $i->category ?? '—',
            $i->sku ?? '—',
            $i->stock,
            $i->unit ?? '—',
            $i->status ?? '—',
            $i->year ?? '—',
        ])->toArray();

        return compact('headers', 'rows');
    }
}
