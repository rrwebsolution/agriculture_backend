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
        'years_in_fishing', 'org_member', 'org_name', 'boat_name', 'boat_type',
        'engine_hp', 'registration_no', 'gear_type', 'gear_units', 'fishing_area',
        'farm_name', 'farm_owner', 'farm_location', 'farm_type', 'farm_size',
        'species_cultured', 'permit_no', 'permit_date_issued', 'permit_expiry',
        'inspection_status', 'beneficiary_program', 'assistance_type',
        'date_released', 'quantity', 'funding_source', 'status'
    ];

    // Relasyon sa Barangay
    public function barangay()
    {
        return $this->belongsTo(Barangay::class, 'barangay_id');
    }
}