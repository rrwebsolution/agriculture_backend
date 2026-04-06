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
        'total_area', 'farm_coordinates', 
        'topography', 'irrigation_type', 'area_breakdown',
        'is_main_livelihood', 'is_coop_member', 'cooperative_id',
        'program_name', 'assistance_type', 'date_released', 
        'quantity', 'total_cost', 'funding_source', 'status',
        'farms_list', 'assistances_list',    
    ];

    protected $casts = [
        'farm_coordinates' => 'array',
        'farms_list' => 'array',      
        'assistances_list' => 'array', 
        'cooperative_id' => 'array', 
    ];

    // 🌟 I-APPEND KINI PARA INIG FETCH SA FARMER, MUPAKITA DAYUN ANG COOPERATIVE DATA
    protected $appends = ['assigned_cooperatives'];

    public function barangay() 
    { 
        return $this->belongsTo(Barangay::class, 'barangay_id'); 
    }

    public function farmLocation() 
    { 
        return $this->belongsTo(Barangay::class, 'farm_barangay_id'); 
    }

    public function crop() 
    { 
        return $this->belongsTo(Crop::class, 'crop_id'); 
    }

    // 🌟 CUSTOM ACCESSOR: FETCH THE ACTUAL COOPERATIVE DATA BASED SA ARRAY MGA IDs
    public function getAssignedCooperativesAttribute()
    {
        if (empty($this->cooperative_id)) {
            return [];
        }

        // Return the Cooperative details (id, name, type) based on IDs inside the array
        return Cooperative::whereIn('id', $this->cooperative_id)
                ->get(['id', 'name', 'type', 'status', 'registration', 'org_type']);
    }

    public function plantings()
    {
        return $this->hasMany(Planting::class);
    }

    public function harvests()
    {
        return $this->hasMany(Harvest::class);
    }
}