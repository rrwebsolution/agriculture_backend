<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_no',
        'first_name',
        'middle_name',
        'last_name',
        'suffix',
        'email',
        'contact_no',
        'position',
        'department',
        'division',
        'employment_type',
        'status',
        'supervisor_id',
        'work_location',
        'current_assignment',
        'face_reference_image',
    ];

    public function supervisor()
    {
        return $this->belongsTo(self::class, 'supervisor_id');
    }

    public function subordinates()
    {
        return $this->hasMany(self::class, 'supervisor_id');
    }

    public function technicianLogs()
    {
        return $this->hasMany(TechnicianLog::class);
    }
}
