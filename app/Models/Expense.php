<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Expense extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'ref_no',
        'item',
        'category',
        'project',
        'amount',
        'date_incurred',
        'status',
        'remarks'
    ];

    protected $casts = [
        'date_incurred' => 'date',
        'amount' => 'float',
    ];

    // Auto-generate Reference Number on creation
    protected static function booted()
    {
        static::creating(function ($expense) {
            $year = date('Y');
            
            // 🌟 FIX: Pangitaon ang pinaka-ulahi nga record karong tuiga (apil ang na-delete)
            $lastExpense = self::withTrashed()
                               ->where('ref_no', 'like', "EXP-{$year}-%")
                               ->orderBy('id', 'desc')
                               ->first();

            if ($lastExpense) {
                // Kung naay record, kuhaon ang last 4 digits ug i-add ang 1
                $lastSequence = (int) substr($lastExpense->ref_no, -4);
                $nextSequence = $lastSequence + 1;
            } else {
                // Kung walay record karong tuiga, magsugod sa 1
                $nextSequence = 1;
            }

            // I-format og balik: EXP-2026-0004
            $expense->ref_no = 'EXP-' . $year . '-' . str_pad($nextSequence, 4, '0', STR_PAD_LEFT);
        });
    }
}