<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Grupa extends Model
{
    protected $table = 'grupy';

    protected $fillable = ['kod', 'opis'];

    public function planRocznyGrupa()
    {
        return $this->hasOne(PlanRocznyGrupa::class);
    }
}
