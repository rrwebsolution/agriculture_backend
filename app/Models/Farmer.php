<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Farmer extends Model
{
    use HasFactory;

    protected $fillable = [
        'system_id', 'rsbsa_no', 'first_name', 'middle_name', 'last_name', 'suffix',
        'gender', 'dob', 'barangay_id', 'address_details', 'contact_no',
        'farm_barangay_id', 'farm_sitio', 'crop_id', 'ownership_type', 
        'total_area', 'topography', 'irrigation_type', 'area_breakdown',
        'is_main_livelihood', 'is_coop_member', 'cooperative_id',
        'program_name', 'assistance_type', 'date_released', 
        'quantity', 'total_cost', 'funding_source', 'status',
    ];

    public function barangay() { return $this->belongsTo(Barangay::class, 'barangay_id'); }
    public function farmLocation() { return $this->belongsTo(Barangay::class, 'farm_barangay_id'); }
    public function crop() { return $this->belongsTo(Crop::class, 'crop_id'); }
    public function cooperative() { return $this->belongsTo(Cooperative::class, 'cooperative_id'); }
}