<?php

namespace App\Http\Controllers;

use App\Models\Grupa;
use App\Models\PlanRoczny;
use App\Models\PlanRocznyGrupa;
use Illuminate\Http\Request;

class PlanRocznyController extends Controller
{
    public function edit()
    {
        $plan = PlanRoczny::first();
        if (! $plan) {
            $plan = new PlanRoczny();
        }
        $grupy = Grupa::orderBy('kod')->with('planRocznyGrupa')->get();
        return view('plan-roczny.edit', compact('plan', 'grupy'));
    }

    public function update(Request $request)
    {
        $plan = PlanRoczny::first();
        if (! $plan) {
            $plan = new PlanRoczny();
        }
        $plan->fill($request->validate([
            'direct_ogolne_plan' => 'nullable|numeric',
            'indirect_plan' => 'nullable|numeric',
            'finansowe_ogolne_plan' => 'nullable|numeric',
        ]))->save();

        $grupy = Grupa::orderBy('kod')->get();
        foreach ($grupy as $g) {
            $prg = PlanRocznyGrupa::firstOrNew(['grupa_id' => $g->id]);
            $prg->grupa_id = $g->id;
            $prg->sales_plan = $request->input("grupa_{$g->id}_sales");
            $prg->cos_plan = $request->input("grupa_{$g->id}_cos");
            $prg->direct_plan = $request->input("grupa_{$g->id}_direct");
            $prg->save();
        }

        return redirect()->route('plan-roczny.edit')->with('success', 'Plan roczny został zapisany.');
    }
}
