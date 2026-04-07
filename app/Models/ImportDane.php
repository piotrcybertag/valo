<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportDane extends Model
{
    protected $table = 'import_danych';

    protected $fillable = [
        'import_id', 'nr', 'wn4', 'ma4', 'wn5', 'ma5',
    ];

    protected $casts = [
        'wn4' => 'decimal:2',
        'ma4' => 'decimal:2',
        'wn5' => 'decimal:2',
        'ma5' => 'decimal:2',
    ];

    /** Suma wn4+wn5 (dla kompatybilności z raportem P&L). */
    public function getWnAttribute(): float
    {
        return (float) ($this->wn4 ?? 0) + (float) ($this->wn5 ?? 0);
    }

    /** Suma ma4+ma5 (dla kompatybilności z raportem P&L). */
    public function getMaAttribute(): float
    {
        return (float) ($this->ma4 ?? 0) + (float) ($this->ma5 ?? 0);
    }

    public function import()
    {
        return $this->belongsTo(Import::class);
    }
}
