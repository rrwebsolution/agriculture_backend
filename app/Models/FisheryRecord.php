<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FisheryRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'fishr_id',
        'name',
        'gender',
        'contact_no',
        'boat_name',
        'gear_type',
        'fishing_area',
        'catch_species',
        'yield',
        'market_value',
        'hours_spent_fishing',
        'vessel_catch_entries',
        'date',
    ];

    protected $casts = [
        'yield' => 'float',
        'market_value' => 'float',
        'hours_spent_fishing' => 'float',
        'vessel_catch_entries' => 'array',
        'date' => 'date:Y-m-d',
    ];
}
