<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Barangay extends Model
{
    protected $fillable = [
        'name', 'type'
    ];

    /**
     * 🌟 AUTOMATIC CODE GENERATION LOGIC 🌟
     */
    protected static function booted()
    {
        static::creating(function ($barangay) {
            // 1. Kuhaon ang pinaka-ulahing barangay record
            $latestBarangay = self::orderBy('id', 'desc')->first();

            if (!$latestBarangay) {
                // Kung wala pa'y sulod ang DB, magsugod sa 1001
                $barangay->code = 'BRGY-1001';
            } else {
                // 2. Kuhaon ang numero gikan sa karaan nga code (e.g., 'BRGY-1001' -> 1001)
                $number = str_replace('BRGY-', '', $latestBarangay->code);
                
                // 3. Pun-an og isa ang numero ug i-format balik
                $barangay->code = 'BRGY-' . (intval($number) + 1);
            }
        });
    }

    public function farmers()
    {
        return $this->hasMany(Farmer::class);
    }

    public function cooperatives()
    {
        return $this->hasMany(Cooperative::class);
    }
    public function crops()
    {
        return $this->hasMany(Crop::class);
    }
    public function programs()
    {
        return $this->hasMany(Program::class);
    }

    public function fisherfolks() {
        return $this->hasMany(FisherFolk::class, 'barangay_id');
    }
   
}
