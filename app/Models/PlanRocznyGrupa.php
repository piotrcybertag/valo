<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlanRocznyGrupa extends Model
{
    protected $table = 'plan_roczny_grupa';

    protected $fillable = ['grupa_id', 'sales_plan', 'cos_plan', 'direct_plan'];

    protected $casts = [
        'sales_plan' => 'decimal:2',
        'cos_plan' => 'decimal:2',
        'direct_plan' => 'decimal:2',
    ];

    public function grupa()
    {
        return $this->belongsTo(Grupa::class);
    }
}
