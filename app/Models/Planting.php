<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Planting extends Model
{
    use HasFactory;

    protected $fillable = [
        'farmer_id',
        'barangay_id', // 🌟 Changed from cluster_id
        'crop_id',
        'area',
        'date_planted',
        'est_harvest',
        'status',
    ];

    // Relationships
    public function farmer()
    {
        return $this->belongsTo(Farmer::class);
    }

    // 🌟 Changed to Barangay
    public function barangay()
    {
        return $this->belongsTo(Barangay::class);
    }

    public function crop()
    {
        return $this->belongsTo(Crop::class);
    }

    public function statusHistory()
    {
        return $this->hasMany(PlantingStatusHistory::class)->latest();
    }

}