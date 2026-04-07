<?php

namespace App\Http\Controllers;

use App\Models\Grupa;
use App\Models\PlanKont;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PlanKontController extends Controller
{
    /** Wartości „Rodzaj pozycji” zgodne z plikiem planu kont (CSV). */
    public static function rodzajePozycji(): array
    {
        return [
            'Direct costs',
            'Indirect costs',
            'Costs of sales',
            'Operational costs',
            'Income PC01',
            'Income PC02',
            'Income PC03',
            'Income PC04',
            'Income PC05',
            'Income PC06',
            'Income services',
            'Income others',
            'Income operational',
            'Financial income',
            'Financial costs',
        ];
    }

    public function index()
    {
        $items = PlanKont::orderBy('nr')->get();
        return view('plan-kont.index', compact('items'));
    }

    public function create()
    {
        $rodzajePozycji = static::rodzajePozycji();
        $grupy = Grupa::orderBy('kod')->get();
        return view('plan-kont.create', compact('rodzajePozycji', 'grupy'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nr' => 'nullable|string|max:50',
            'grupa' => 'nullable|string|max:255',
            'nazwa' => 'nullable|string|max:255',
            'rodzaj_pozycji' => 'nullable|string|max:255',
        ]);
        PlanKont::create($validated);
        return redirect()->route('plan-kont.index')->with('success', 'Pozycja została dodana.');
    }

    public function show(string $plan_kont)
    {
        $planKont = PlanKont::findOrFail($plan_kont);
        return redirect()->route('plan-kont.edit', $planKont);
    }

    public function edit(string $plan_kont)
    {
        $planKont = PlanKont::findOrFail($plan_kont);
        $rodzajePozycji = static::rodzajePozycji();
        $grupy = Grupa::orderBy('kod')->get();
        return view('plan-kont.edit', compact('planKont', 'rodzajePozycji', 'grupy'));
    }

    public function update(Request $request, string $plan_kont)
    {
        $planKont = PlanKont::findOrFail($plan_kont);
        $validated = $request->validate([
            'nr' => 'nullable|string|max:50',
            'grupa' => 'nullable|string|max:255',
            'nazwa' => 'nullable|string|max:255',
            'rodzaj_pozycji' => 'nullable|string|max:255',
        ]);
        $planKont->update($validated);

        if (array_key_exists('rodzaj_pozycji', $validated) && $planKont->nr) {
            $prefix = $planKont->nr . '-';
            $updated = PlanKont::where('nr', 'like', $prefix . '%')
                ->update(['rodzaj_pozycji' => $validated['rodzaj_pozycji']]);
            if ($updated > 0) {
                return redirect()->route('plan-kont.index')
                    ->with('success', "Pozycja została zaktualizowana. Rodzaj pozycji ustawiono także dla {$updated} kont podrzędnych.");
            }
        }

        return redirect()->route('plan-kont.index')->with('success', 'Pozycja została zaktualizowana.');
    }

    public function destroy(string $plan_kont)
    {
        $planKont = PlanKont::findOrFail($plan_kont);
        $planKont->delete();
        return redirect()->route('plan-kont.index')->with('success', 'Pozycja została usunięta.');
    }

    public function destroyAll(Request $request)
    {
        $request->validate([
            'confirm' => 'required|in:1',
            'potwierdzenie' => 'required|in:TAK',
        ], [
            'potwierdzenie.required' => 'Wymagane jest wpisanie TAK w oknie potwierdzenia.',
            'potwierdzenie.in' => 'Należy wpisać słownie TAK, aby potwierdzić usunięcie.',
        ]);
        PlanKont::truncate();
        return redirect()->route('plan-kont.index')->with('success', 'Wszystkie pozycje planu kont zostały usunięte.');
    }

    /**
     * Przypisuje grupy do pozycji planu kont na podstawie wystąpienia kodu grupy w nazwie konta.
     * Przy wielu dopasowaniach wybierana jest grupa o najdłuższym kodzie.
     */
    public function przyjmijGrupy(Request $request)
    {
        $grupy = Grupa::whereNotNull('kod')->where('kod', '!=', '')->orderByRaw('LENGTH(kod) DESC')->get();

        if ($grupy->isEmpty()) {
            return redirect()->route('plan-kont.index')
                ->with('error', 'Brak grup w kartotece. Dodaj grupy w Kartoteki → Grupy.');
        }

        $updated = 0;
        foreach (PlanKont::all() as $planKont) {
            $nazwa = $planKont->nazwa ?? '';
            $bestGrupa = null;
            foreach ($grupy as $grupa) {
                if (str_contains($nazwa, $grupa->kod)) {
                    $bestGrupa = $grupa->kod;
                    break;
                }
            }
            if ($bestGrupa !== null && $planKont->grupa !== $bestGrupa) {
                $planKont->update(['grupa' => $bestGrupa]);
                $updated++;
            }
        }

        return redirect()->route('plan-kont.index')
            ->with('success', "Przypisano grupy. Zaktualizowano {$updated} pozycji planu kont.");
    }

    /**
     * Pobiera plan kont jako CSV w formacie importu: Nr;Nazwa;Rodzaj pozycji
     */
    public function pobierzCsv(): StreamedResponse
    {
        $items = PlanKont::orderBy('nr')->get();

        return response()->streamDownload(function () use ($items) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($out, ['Nr', 'Nazwa', 'Rodzaj pozycji'], ';');
            foreach ($items as $item) {
                fputcsv($out, [
                    $item->nr ?? '',
                    $item->nazwa ?? '',
                    $item->rodzaj_pozycji ?? '',
                ], ';');
            }
            fclose($out);
        }, 'plan-kont.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
