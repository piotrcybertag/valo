<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlanRoczny extends Model
{
    protected $table = 'plan_roczny';

    protected $fillable = ['direct_ogolne_plan', 'indirect_plan', 'finansowe_ogolne_plan'];

    protected $casts = [
        'direct_ogolne_plan' => 'decimal:2',
        'indirect_plan' => 'decimal:2',
        'finansowe_ogolne_plan' => 'decimal:2',
    ];
}
