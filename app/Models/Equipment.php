<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Equipment extends Model
{
    use HasFactory;

    // FIX: Tell Laravel to use 'equipments' instead of 'equipment'
    protected $table = 'equipments';

    protected $fillable = [
        'sku',
        'name',
        'type',
        'program',
        'condition',
        'status',
        'last_check'
    ];

    /**
     * Relationship with Cooperatives (Many-to-Many)
     */
    public function cooperatives()
    {
        // Ensure the pivot table name matches your migration
        return $this->belongsToMany(Cooperative::class, 'equipment_cooperative');
    }
}