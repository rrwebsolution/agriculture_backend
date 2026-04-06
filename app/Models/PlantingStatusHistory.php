<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlantingStatusHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'planting_id',
        'status',
        'remarks',
    ];

    public function planting()
    {
        return $this->belongsTo(Planting::class);
    }
}
