<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Import extends Model
{
    protected $fillable = ['data_okresu', 'nazwa_pliku'];

    protected $casts = [
        'data_okresu' => 'date',
    ];

    public function dane()
    {
        return $this->hasMany(ImportDane::class);
    }

    /** Zwraca okres w formacie "Marzec 2025" */
    public function getOkresNazwaAttribute(): ?string
    {
        if (! $this->data_okresu) {
            return null;
        }
        $miesiace = ['Styczeń', 'Luty', 'Marzec', 'Kwiecień', 'Maj', 'Czerwiec', 'Lipiec', 'Sierpień', 'Wrzesień', 'Październik', 'Listopad', 'Grudzień'];
        return $miesiace[(int) $this->data_okresu->format('n') - 1] . ' ' . $this->data_okresu->format('Y');
    }
}
