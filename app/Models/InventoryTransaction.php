<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryTransaction extends Model
{
    protected $fillable = [
    'inventory_id', 'type', 'quantity', 'source_supplier', 
    'beneficiary_type', 'recipient_name', 'rsbsa_no', 'transaction_date',
    'remarks' // <-- GIDUGANG
    ];

    public function inventory() {
        return $this->belongsTo(Inventory::class);
    }
}
