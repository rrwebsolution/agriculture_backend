<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Cooperative extends Model
{
    use HasFactory;

    protected $fillable = [
        'system_id', 'cda_no', 'name', 'type', 'chairman', 
        'contact_no', 'barangay_id', 'address_details', 
        'member_count', 'capital_cbu', 'status'
    ];

    public function barangay()
    {
        return $this->belongsTo(Barangay::class);
    }

    public function farmers()
    {
        return $this->hasMany(Farmer::class);
    }

    public function fisheries() {
        return $this->hasMany(Fishery::class, 'cooperative_id');
    }

     public function crops()
    {
        return $this->hasMany(Crop::class);
    }

}
