<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NurseryRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'record_date',
        'activity',
        'crop_item',
        'quantity',
        'unit',
        'nursery_site',
        'remarks',
        'metadata',
    ];

    protected $casts = [
        'record_date' => 'date',
        'quantity' => 'decimal:2',
        'metadata' => 'array',
    ];
}
