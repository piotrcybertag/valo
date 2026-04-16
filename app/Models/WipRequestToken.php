<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WipRequestToken extends Model
{
    protected $fillable = ['token', 'email', 'rok', 'miesiac', 'expires_at'];

    protected $casts = [
        'rok' => 'integer',
        'miesiac' => 'integer',
        'expires_at' => 'datetime',
    ];
}
