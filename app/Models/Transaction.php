<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'type',
        'amount',
        'description',
        'date',
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
    ];

    protected $appends = ['formatted_date'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getFormattedDateAttribute()
    {
        if (!$this->date) return '';
        // Format: d-M-Y (e.g., 01-Ene-2026)
        // Set locale to Spanish for month names
        \Carbon\Carbon::setLocale('es');
        $date = \Carbon\Carbon::parse($this->date);
        return $date->translatedFormat('d-M-Y');
    }
}
