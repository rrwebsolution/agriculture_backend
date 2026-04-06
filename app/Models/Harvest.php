<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Harvest extends Model
{
    use HasFactory;

    protected $fillable = [
        'farmer_id', 'barangay_id', 'crop_id', // 🌟 UPDATED: barangay_id
        'dateHarvested', 'quantity', 'quality', 'value'
    ];

    public function farmer() {
        return $this->belongsTo(Farmer::class);
    }

    // 🌟 UPDATED: Ilisan ang public function cluster() ngadto sa barangay()
    public function barangay() {
        return $this->belongsTo(Barangay::class);
    }

    public function crop() {
        return $this->belongsTo(Crop::class);
    }

    public function harvests() 
    {
        return $this->hasMany(Harvest::class); 
    }
}