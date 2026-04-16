<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportDane extends Model
{
    protected $table = 'import_danych';

    protected $fillable = [
        'import_id', 'nr', 'wn2', 'ma2', 'wn3', 'ma3',
    ];

    protected $casts = [
        'wn2' => 'decimal:2',
        'ma2' => 'decimal:2',
        'wn3' => 'decimal:2',
        'ma3' => 'decimal:2',
    ];

    /** Suma wn2+wn3 (bieżący + narastająco). */
    public function getWnAttribute(): float
    {
        return (float) ($this->wn2 ?? 0) + (float) ($this->wn3 ?? 0);
    }

    /** Suma ma2+ma3 (bieżący + narastająco). */
    public function getMaAttribute(): float
    {
        return (float) ($this->ma2 ?? 0) + (float) ($this->ma3 ?? 0);
    }

    public function import()
    {
        return $this->belongsTo(Import::class);
    }
}
