<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Barangay extends Model
{
    // 🌟 GIDUGANG ANG LATITUDE UG LONGITUDE DIRI
    protected $fillable = [
        'name', 'code', 'type', 'latitude', 'longitude'
    ];

    protected static function booted()
    {
        static::creating(function ($barangay) {
            if (blank($barangay->code)) {
                $latestNumber = self::query()
                    ->where('code', 'regexp', '^BRGY-[0-9]+$')
                    ->selectRaw('MAX(CAST(SUBSTRING(code, 6) AS UNSIGNED)) as number')
                    ->value('number');

                $barangay->code = 'BRGY-' . max(1001, ((int) $latestNumber) + 1);
            }
        });
    }

    public function cooperatives() { return $this->hasMany(Cooperative::class); }
    public function crops() { return $this->hasMany(Crop::class); }
    public function programs() { return $this->hasMany(Program::class); }
    public function fisherfolks() { return $this->hasMany(Fisherfolk::class, 'barangay_id'); }
    public function farmers() { return $this->hasMany(Farmer::class); }
    public function harvests() { return $this->hasMany(Harvest::class); }

    public function plantings() 
    { 
        return $this->hasMany(Planting::class, 'barangay_id'); 
    }
}
