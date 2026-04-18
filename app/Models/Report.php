<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'type',
        'module',
        'period_from',
        'period_to',
        'generated_by',
        'generated_at',
        'format',
        'status',
        'notes',
        'file_path',
    ];

    protected $casts = [
        'period_from' => 'date:Y-m-d',
        'period_to' => 'date:Y-m-d',
        'generated_at' => 'datetime',
    ];
}
