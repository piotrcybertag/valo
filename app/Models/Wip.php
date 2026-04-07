<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Wip extends Model
{
    protected $fillable = ['rok', 'miesiac', 'nazwa_projektu', 'wartosc'];

    protected $casts = [
        'rok' => 'integer',
        'miesiac' => 'integer',
        'wartosc' => 'decimal:2',
    ];

    public function getOkresNazwaAttribute(): string
    {
        $miesiace = ['Styczeń', 'Luty', 'Marzec', 'Kwiecień', 'Maj', 'Czerwiec', 'Lipiec', 'Sierpień', 'Wrzesień', 'Październik', 'Listopad', 'Grudzień'];
        $m = (int) $this->miesiac;
        $nazwa = $miesiace[$m - 1] ?? (string) $m;

        return $nazwa . ' ' . $this->rok;
    }
}
