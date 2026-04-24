<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DangerZone extends Model
{
    protected $fillable = [
        'name',
        'zone_type',
        'description',
        'status',
        'color',
        'fill_color',
        'positions',
    ];

    protected $casts = [
        'positions' => 'array',
    ];
}
