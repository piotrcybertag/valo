<?php

namespace App\Http\Controllers;

use App\Models\Wip;
use Illuminate\Http\Request;

class WipController extends Controller
{
    public function index()
    {
        $items = Wip::query()
            ->orderByDesc('rok')
            ->orderByDesc('miesiac')
            ->orderBy('nazwa_projektu')
            ->get();

        return view('wip.index', compact('items'));
    }

    public function create()
    {
        return view('wip.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'rok' => 'required|integer|min:2000|max:2100',
            'miesiac' => 'required|integer|min:1|max:12',
            'nazwa_projektu' => 'required|string|max:255',
            'wartosc' => 'required|numeric',
        ], [
            'rok.required' => 'Wybierz rok.',
            'miesiac.required' => 'Wybierz miesiąc.',
            'nazwa_projektu.required' => 'Podaj nazwę projektu.',
            'wartosc.required' => 'Podaj wartość.',
        ]);

        Wip::create($validated);
        return redirect()->route('wip.index')->with('success', 'Wpis WIP został dodany.');
    }

    public function show(Wip $wip)
    {
        return view('wip.show', compact('wip'));
    }

    public function edit(Wip $wip)
    {
        return view('wip.edit', compact('wip'));
    }

    public function update(Request $request, Wip $wip)
    {
        $validated = $request->validate([
            'rok' => 'required|integer|min:2000|max:2100',
            'miesiac' => 'required|integer|min:1|max:12',
            'nazwa_projektu' => 'required|string|max:255',
            'wartosc' => 'required|numeric',
        ]);

        $wip->update($validated);
        return redirect()->route('wip.index')->with('success', 'Wpis WIP został zaktualizowany.');
    }

    public function destroy(Wip $wip)
    {
        $wip->delete();
        return redirect()->route('wip.index')->with('success', 'Wpis WIP został usunięty.');
    }
}
