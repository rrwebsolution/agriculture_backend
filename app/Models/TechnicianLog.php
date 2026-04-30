<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TechnicianLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'log_date',
        'location_name',
        'latitude',
        'longitude',
        'assignment',
        'status',
        'notes',
        'face_verified',
        'face_verified_at',
        'face_match_score',
        'verification_photo',
    ];

    protected $casts = [
        'log_date' => 'date:Y-m-d',
        'latitude' => 'float',
        'longitude' => 'float',
        'face_verified' => 'boolean',
        'face_verified_at' => 'datetime:Y-m-d H:i:s',
        'face_match_score' => 'float',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
