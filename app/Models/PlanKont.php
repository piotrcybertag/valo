<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlanKont extends Model
{
    protected $table = 'plan_konts';

    protected $fillable = ['nr', 'grupa', 'nazwa', 'rodzaj_pozycji'];
}
