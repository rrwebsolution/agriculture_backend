<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SystemLabel extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'group',
        'value',
        'default_value',
        'description',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'updated_by' => 'integer',
        ];
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function effectiveValue(): string
    {
        return $this->value ?? $this->default_value;
    }
}
