<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Crop extends Model
{
    use HasFactory;

    protected $fillable = [
        'category',
        'remarks'
    ];

    // Ito yung relationship na gagamitin natin pang count!
    public function registeredFarmers()
    {
        return $this->hasMany(Farmer::class, 'crop_id');
    }
}