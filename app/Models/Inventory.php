<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    protected $fillable = [
        'name', 'commodity', 'category', 'sku', 
        'batch', 'stock', 'unit', 'threshold', 'status',
        'recipients', 'year', 'remarks' // <-- GIDUGANG KINI
    ];

    protected static function boot()
    {
        parent::boot();
        static::saving(function ($model) {
            if ($model->stock <= 0) {
                $model->status = 'Out of Stock';
            } elseif ($model->stock <= $model->threshold) {
                $model->status = 'Low Stock';
            } else {
                $model->status = 'In Stock';
            }
        });
    }

    public function transactions() {
    return $this->hasMany(InventoryTransaction::class)->orderBy('transaction_date', 'desc');
}
}
