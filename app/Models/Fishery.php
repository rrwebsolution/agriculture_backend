<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Fishery extends Model
{
    use HasFactory;

    protected $fillable = [
        'fishr_id',
        'fisherfolk_id',
        'boat_name',
        'gear_type',
        'location_id',
        'catch_species',
        'yield',
        'date',
    ];

    public function fisherfolk()
    {
        return $this->belongsTo(Farmer::class, 'fisherfolk_id');
    }

    public function location()
    {
        return $this->belongsTo(Barangay::class, 'location_id');
    }
    public function cooperative()
    {
        return $this->belongsTo(Cooperative::class, 'cooperative_id');
    }
}