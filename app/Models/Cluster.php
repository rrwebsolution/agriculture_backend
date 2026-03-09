<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cluster extends Model
{
    protected $fillable = ['name', 'description', 'status'];

    // Define the relationship
    public function users()
    {
        return $this->hasMany(User::class);
    }

    // This makes 'staffCount' available automatically in JSON responses
    protected $appends = ['staffCount'];

    public function getStaffCountAttribute()
    {
        return $this->users()->count();
    }
}