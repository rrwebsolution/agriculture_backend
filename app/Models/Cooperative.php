<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Cooperative extends Model
{
    use HasFactory;

    // 🌟 Updated fillable (Removed member_count, Added registration & org_type)
    protected $fillable = [
        'system_id', 'cda_no', 'name', 'type', 'registration', 'org_type',
        'chairman', 'contact_no', 'barangay_id', 'address_details', 
        'capital_cbu', 'status'
    ];

    public function barangay()
    {
        return $this->belongsTo(Barangay::class);
    }

    // Since Farmer's cooperative_id is a JSON Array, standard hasMany might fail. 
    // We handle the Farmer mapping inside the controller instead for accuracy and speed.
    
    public function fisheries() {
        return $this->hasMany(Fishery::class, 'cooperative_id');
    }

    public function crops()
    {
        return $this->hasMany(Crop::class);
    }
}