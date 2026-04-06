<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Crop extends Model
{
    use HasFactory;

    protected $fillable = [
        'category',
        'remarks'
    ];


    public function registeredFarmers() {
    return $this->hasMany(Farmer::class)->with(['barangay']); // ✅ DAPAT ING-ANI NALANG
}

    public function plantings()
    {
        return $this->hasMany(Planting::class, 'crop_id');
    }

}