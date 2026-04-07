<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\PrzerobCsvTrait;
use App\Models\ImportProjekt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ImportProjektController extends Controller
{
    use PrzerobCsvTrait;

    public function index()
    {
        $projekty = ImportProjekt::withCount('dane')->orderByDesc('created_at')->get();
        return view('piatki.index', compact('projekty'));
    }

    public function show(ImportProjekt $importProjekt)
    {
        $importProjekt->load('dane');
        return view('piatki.show', compact('importProjekt'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'plik_csv' => 'required|file|mimes:csv,txt|max:20480',
        ], [
            'plik_csv.required' => 'Wybierz plik CSV.',
            'plik_csv.file' => 'Przekaż prawidłowy plik.',
            'plik_csv.mimes' => 'Plik musi mieć rozszerzenie .csv lub .txt.',
        ]);

        if ($validator->fails()) {
            return redirect()->route('import.create')
                ->withErrors($validator)
                ->withInput();
        }

        $file = $request->file('plik_csv');
        $path = $file->getRealPath();

        [$outputLines, $headers] = $this->przerobCsvHeaders($path);

        if (empty($outputLines)) {
            return redirect()->route('import.create')
                ->with('error', 'Plik CSV jest pusty.');
        }

        array_shift($outputLines);
        $headerLower = array_map(fn ($c) => mb_strtolower(trim($c)), $headers);

        $idxNr = $this->findColumnIndex($headerLower, ['nr']);
        $idxNazwa = $this->findColumnIndex($headerLower, ['nazwa']);

        if ($idxNr === null || $idxNazwa === null) {
            return redirect()->route('import.create')
                ->with('error', 'Brak wymaganych kolumn Nr i Nazwa.');
        }

        $wnMaCols = [];
        foreach ($headerLower as $i => $h) {
            if (preg_match('/^(wn|ma)(\d+)$/', $h, $m)) {
                $wnMaCols[$i] = $m[1] . $m[2];
            }
        }

        $rows = [];
        foreach ($outputLines as $line) {
            $cols = str_getcsv($line, ';');
            $nr = trim($cols[$idxNr] ?? '');
            if ($nr === '') {
                continue;
            }
            $nazwa = trim($cols[$idxNazwa] ?? '');
            $wartosci = [];
            foreach ($wnMaCols as $colIdx => $colName) {
                $wartosci[$colName] = $this->parseAmount($cols[$colIdx] ?? '');
            }
            $rows[] = [
                'nr' => $nr ?: null,
                'nazwa' => $nazwa ?: null,
                'wartosci' => $wartosci,
            ];
        }

        if (empty($rows)) {
            return redirect()->route('import.create')
                ->with('error', 'W pliku nie znaleziono wierszy z danymi.');
        }

        try {
            DB::transaction(function () use ($rows) {
                $import = ImportProjekt::create([
                    'nazwa_pliku' => 'import_' . now()->format('Y-m-d_His') . '.csv',
                ]);
                foreach ($rows as $row) {
                    $import->dane()->create($row);
                }
            });
        } catch (\Throwable $e) {
            return redirect()->route('import.create')
                ->with('error', 'Błąd importu: ' . $e->getMessage());
        }

        return redirect()->route('piatki.index')
            ->with('success', 'Zaimportowano ' . count($rows) . ' wierszy danych projektowych.');
    }

    public function destroy(ImportProjekt $importProjekt)
    {
        $importProjekt->delete();
        return redirect()->route('piatki.index')->with('success', 'Import projektów został usunięty.');
    }

    private function findColumnIndex(array $headerLower, array $possibleNames): ?int
    {
        foreach ($possibleNames as $name) {
            $idx = array_search($name, $headerLower, true);
            if ($idx !== false) {
                return $idx;
            }
        }
        return null;
    }
}
