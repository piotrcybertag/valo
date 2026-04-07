<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportProjekt extends Model
{
    protected $table = 'import_projekty';

    protected $fillable = ['nazwa_pliku'];

    public function dane()
    {
        return $this->hasMany(ImportProjektDane::class);
    }
}
