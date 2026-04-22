<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Inventory;
use App\Models\Farmer;         // Siguroha nga husto ang model names
use App\Models\Fisherfolk;     // o Fishery base sa imong model
use App\Models\Cooperative;
use App\Models\InventoryTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InventoryController extends Controller
{
    private const CATEGORY_PREFIX_MAP = [
        'Seed distribution' => 'SEED',
        'Fertilizer distribution(Inorganic)' => 'FERT-INORG',
        'Fertilizer distribution(Organic)' => 'FERT-ORG',
        'Commodity based(Package)' => 'COMM',
        'Tools and equipments' => 'TOOL',
    ];

    public function index()
{
    return response()->json([
        // Gigamitan og 'with' para ma-apil ang transactions relationship
        'inventories' => Inventory::with('transactions')->orderBy('created_at', 'desc')->get(),
        'farmers' => Farmer::select('id', 'first_name', 'last_name', 'rsbsa_no')->get(),
        'fisherfolks' => Fisherfolk::select('id', 'first_name', 'last_name', 'system_id', 'registration_no')->get(),
        'cooperatives' => Cooperative::select('id', 'name', 'system_id', 'cda_no')->get(),
    ]);
}

    private function getCategoryPrefix(?string $category): string
    {
        if (!$category) {
            return 'ITEM';
        }

        if (isset(self::CATEGORY_PREFIX_MAP[$category])) {
            return self::CATEGORY_PREFIX_MAP[$category];
        }

        $normalized = strtoupper((string) Str::of($category)
            ->replaceMatches('/[^A-Za-z0-9]+/', '-')
            ->trim('-'));

        return $normalized !== '' ? $normalized : 'ITEM';
    }

    private function nextSequence(string $column, string $prefix, string $year): int
    {
        $likePattern = $prefix . '-' . $year . '-%';

        $latestValue = Inventory::where($column, 'like', $likePattern)
            ->orderByDesc($column)
            ->value($column);

        if (!$latestValue || !preg_match('/(\d+)$/', $latestValue, $matches)) {
            return 1;
        }

        return ((int) $matches[1]) + 1;
    }

    private function generateSku(?string $category, string $year): string
    {
        $prefix = $this->getCategoryPrefix($category);
        $sequence = str_pad((string) $this->nextSequence('sku', $prefix, $year), 3, '0', STR_PAD_LEFT);

        return "{$prefix}-{$year}-{$sequence}";
    }

    private function generateBatch(string $year): string
    {
        $prefix = 'B';
        $sequence = str_pad((string) $this->nextSequence('batch', $prefix, $year), 3, '0', STR_PAD_LEFT);

        return "{$prefix}-{$year}-{$sequence}";
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'category' => 'required|string',
            'sku' => 'nullable|string|unique:inventories,sku',
            'stock' => 'required|integer|min:0',
            'unit' => 'required|string',
            'threshold' => 'required|integer',
            'commodity' => 'nullable|string',
            'batch' => 'nullable|string',
            'recipients' => 'nullable|integer|min:0',
            'year' => 'required|string',
            'remarks' => 'nullable|string',
            // Gidugang nato kini para sa initial transaction source
            'source' => 'nullable|string' 
        ]);

        $validated['year'] = (string) ($validated['year'] ?: now()->year);
        $validated['sku'] = !empty($validated['sku'])
            ? strtoupper($validated['sku'])
            : $this->generateSku($validated['category'] ?? null, $validated['year']);
        $validated['batch'] = !empty($validated['batch'])
            ? strtoupper($validated['batch'])
            : $this->generateBatch($validated['year']);

        $item = DB::transaction(function () use ($validated, $request) {
        $inventory = Inventory::create($validated);

        if ($inventory->stock > 0) {
            InventoryTransaction::create([
                'inventory_id'     => $inventory->id,
                'type'             => 'IN',
                'quantity'         => $inventory->stock,
                'source_supplier'  => $request->source ?? 'INITIAL STOCK REGISTRATION',
                'transaction_date' => now(),
            ]);
        }
        return $inventory;
    });

        return response()->json($item->load('transactions'), 201);
    }

    public function update(Request $request, $id)
    {
        $item = Inventory::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string',
            'category' => 'required|string',
            'sku' => 'nullable|string|unique:inventories,sku,' . $id, 
            'stock' => 'required|integer|min:0',
            'unit' => 'required|string',
            'threshold' => 'required|integer',
            'commodity' => 'nullable|string',
            'batch' => 'nullable|string',
            'recipients' => 'nullable|integer|min:0',
            'year' => 'required|string',
            'remarks' => 'nullable|string'
        ]);

        $validated['year'] = (string) ($validated['year'] ?: $item->year ?: now()->year);
        $validated['sku'] = !empty($validated['sku'])
            ? strtoupper($validated['sku'])
            : ($item->sku ?: $this->generateSku($validated['category'] ?? null, $validated['year']));
        $validated['batch'] = !empty($validated['batch'])
            ? strtoupper($validated['batch'])
            : ($item->batch ?: $this->generateBatch($validated['year']));

        $item->update($validated);
        return response()->json($item);
    }

    public function destroy($id)
    {
        return Inventory::destroy($id);
    }

    public function updateStock(Request $request, $id)
    {
        $item = Inventory::findOrFail($id);

        // 1. Validation
        $validated = $request->validate([
            'type'     => 'required|in:IN,OUT',
            'quantity' => 'required|integer|min:1',
            'date'     => 'required|date',
            // Optional fields gikan sa frontend
            'source'           => 'nullable|string', 
            'beneficiary_type' => 'nullable|string', 
            'recipient'        => 'nullable|string',
            'rsbsa'           => 'nullable|string',
        ]);

        // 2. Logic sa Stock Calculation & Data Mapping
        $transactionData = [
            'inventory_id'     => $item->id,
            'type'             => $validated['type'],
            'quantity'         => $validated['quantity'],
            'transaction_date' => $validated['date'],
        ];

        if ($request->type === 'IN') {
        $item->stock += $request->quantity;
        $actorField = ['source_supplier' => $request->source];
    } else {
        if ($item->stock < $request->quantity) {
            return response()->json(['message' => 'Insufficient stock!'], 422);
        }
        $item->stock -= $request->quantity;
        $actorField = [
            'beneficiary_type' => $request->beneficiary_type,
            'recipient_name'   => $request->recipient,
            'rsbsa_no'         => $request->rsbsa,
        ];
    }

    $item->save();

    // I-save ang transaction record
    \App\Models\InventoryTransaction::create(array_merge([
        'inventory_id'     => $item->id,
        'type'             => $request->type,
        'quantity'         => $request->quantity,
        'transaction_date' => $request->date,
    ], $actorField));

    // 🌟 IMPORTANTE: I-load ang transactions sa dili pa i-return
    // Kini moseguro nga ang frontend makadawat sa updated history
    return response()->json($item->load('transactions'));
    }

    public function destroyTransaction($id)
    {
        return DB::transaction(function () use ($id) {
            $transaction = InventoryTransaction::findOrFail($id);
            $inventory = Inventory::findOrFail($transaction->inventory_id);

            if ($transaction->type === 'IN') {
                // Kon i-revert ang Stock IN, kinahanglan makuhaan ang stock
                // I-check kung dili ba mo-negative ang stock
                if ($inventory->stock < $transaction->quantity) {
                    return response()->json(['message' => 'Cannot revert. Stock already distributed.'], 422);
                }
                $inventory->stock -= $transaction->quantity;
            } else {
                // Kon i-revert ang Stock OUT, mabalik ang stock
                $inventory->stock += $transaction->quantity;
            }

            $inventory->save();
            $transaction->delete();

            return response()->json($inventory->load('transactions'));
        });
    }

    public function revertTransaction(Request $request, $id)
{
    return DB::transaction(function () use ($id, $request) {
        $originalTx = InventoryTransaction::findOrFail($id);
        $inventory = Inventory::findOrFail($originalTx->inventory_id);

        // 1. ADJUST ANG STOCK SA INVENTORY
        if ($originalTx->type === 'IN') {
            // Kon i-revert ang IN, makuhaan ang stock (Murag OUT)
            if ($inventory->stock < $originalTx->quantity) {
                return response()->json(['message' => 'Cannot revert. Stock has already been distributed.'], 422);
            }
            $inventory->stock -= $originalTx->quantity;
            $mathSign = '-';
        } else {
            // Kon i-revert ang OUT, mabalik ang stock (Murag IN)
            $inventory->stock += $originalTx->quantity;
            $mathSign = '+';
        }
        $inventory->save();

        // 2. PAGHIMO OG BAG-ONG 'REVERT' TRANSACTION RECORD
        InventoryTransaction::create([
            'inventory_id'     => $inventory->id,
            'type'             => 'REVERT',
            'quantity'         => $originalTx->quantity,
            // Kopyahon kinsa ang na-involve para sa tracking
            'source_supplier'  => $originalTx->source_supplier,
            'beneficiary_type' => $originalTx->beneficiary_type,
            'recipient_name'   => $originalTx->recipient_name,
            'rsbsa_no'         => $originalTx->rsbsa_no,
            'transaction_date' => now()->toDateString(),
            // Ibutang ang rason ug reference ID
            'remarks'          => "Reverted Transaction #{$originalTx->id} ({$mathSign}{$originalTx->quantity})"
        ]);

        return response()->json($inventory->load('transactions'));
    });
}
}
