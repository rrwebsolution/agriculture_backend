<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Barangay extends Model
{
    // 🌟 GIDUGANG ANG LATITUDE UG LONGITUDE DIRI
    protected $fillable = [
        'name', 'type', 'latitude', 'longitude' 
    ];

    protected static function booted()
    {
        static::creating(function ($barangay) {
            $latestBarangay = self::orderBy('id', 'desc')->first();

            if (!$latestBarangay) {
                $barangay->code = 'BRGY-1001';
            } else {
                $number = str_replace('BRGY-', '', $latestBarangay->code);
                $barangay->code = 'BRGY-' . (intval($number) + 1);
            }
        });
    }

    public function cooperatives() { return $this->hasMany(Cooperative::class); }
    public function crops() { return $this->hasMany(Crop::class); }
    public function programs() { return $this->hasMany(Program::class); }
    public function fisherfolks() { return $this->hasMany(FisherFolk::class, 'barangay_id'); }
    public function farmers() { return $this->hasMany(Farmer::class); }
    public function harvests() { return $this->hasMany(Harvest::class); }

    public function plantings() 
    { 
        return $this->hasMany(Planting::class, 'barangay_id'); 
    }
}