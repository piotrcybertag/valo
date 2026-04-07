<?php

namespace App\Http\Controllers;

use App\Models\Grupa;
use Illuminate\Http\Request;

class GrupaController extends Controller
{
    public function index()
    {
        $items = Grupa::orderBy('kod')->get();
        return view('grupy.index', compact('items'));
    }

    public function create()
    {
        return view('grupy.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'kod' => 'nullable|string|max:255',
            'opis' => 'nullable|string|max:255',
        ]);
        Grupa::create($validated);
        return redirect()->route('grupy.index')->with('success', 'Grupa została dodana.');
    }

    public function show(string $grupa)
    {
        $grupa = Grupa::findOrFail($grupa);
        return redirect()->route('grupy.edit', $grupa);
    }

    public function edit(string $grupa)
    {
        $grupa = Grupa::findOrFail($grupa);
        return view('grupy.edit', compact('grupa'));
    }

    public function update(Request $request, string $grupa)
    {
        $grupa = Grupa::findOrFail($grupa);
        $validated = $request->validate([
            'kod' => 'nullable|string|max:255',
            'opis' => 'nullable|string|max:255',
        ]);
        $grupa->update($validated);
        return redirect()->route('grupy.index')->with('success', 'Grupa została zaktualizowana.');
    }

    public function destroy(string $grupa)
    {
        $grupa = Grupa::findOrFail($grupa);
        $grupa->delete();
        return redirect()->route('grupy.index')->with('success', 'Grupa została usunięta.');
    }
}
