<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportProjektDane extends Model
{
    protected $table = 'import_projekt_dane';

    protected $fillable = ['import_projekt_id', 'nr', 'nazwa', 'wartosci'];

    protected $casts = [
        'wartosci' => 'array',
    ];

    public function importProjekt()
    {
        return $this->belongsTo(ImportProjekt::class);
    }
}
