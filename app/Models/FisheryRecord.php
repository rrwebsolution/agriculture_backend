<?php

namespace App\Models;

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
        'date'
    ];
}
