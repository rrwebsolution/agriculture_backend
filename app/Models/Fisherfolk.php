<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Fisherfolk extends Model
{
    use HasFactory;

    protected $table = 'fisherfolks';

    protected $fillable = [
        'system_id', 'first_name', 'middle_name', 'last_name', 'suffix',
        'gender', 'dob', 'age', 'civil_status', 'barangay_id', 'address_details',
        'contact_no', 'education', 'fisher_type', 'is_main_livelihood',
        'years_in_fishing', 'org_member', 'status',
        'farm_name', 'farm_owner', 'farm_location', 'farm_type', 'farm_size',
        'species_cultured', 'permit_no', 'permit_date_issued', 'permit_expiry',
        'inspection_status', 
        
        // 🌟 BAG-ONG JSON FIELDS
        'cooperative_id', 'boats_list', 'assistances_list' 
    ];

    // 🌟 KINI ANG PINAKA IMPORTANTE PARA MA-SAVE OG SAKTO ANG MULTIPLE DATA
    protected $casts = [
        'cooperative_id' => 'array',
        'boats_list' => 'array',
        'assistances_list' => 'array',
    ];

    public function barangay()
    {
        return $this->belongsTo(Barangay::class, 'barangay_id');
    }

    public function catchRecords()
    {
        // Gi-assume nako nga ang 'system_id' sa Fisherfolk mao ang 'fishr_id' sa FisheryRecord
        return $this->hasMany(FisheryRecord::class, 'fishr_id', 'system_id');
    }
}