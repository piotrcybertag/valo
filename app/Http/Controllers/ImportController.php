<?php

namespace App\Http\Controllers;

use App\Models\Import;
use App\Models\ImportDane;
use App\Models\PlanKont;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;

class ImportController extends Controller
{
    public function index()
    {
        $imports = Import::withCount('dane')->orderByDesc('created_at')->get();
        return view('import.index', compact('imports'));
    }

    public function create()
    {
        return view('import.create');
    }

    /** Przerabia plik CSV: unikalne nagłówki, usunięcie spacji w liczbach. Zwraca [lines, filename]. */
    private function przerobPlikCsv(string $path): array
    {
        $lines = $this->readCsvLines($path);
        if (empty($lines)) {
            return [[], null];
        }

        $headerRow = str_getcsv(array_shift($lines), ';');
        $uniqueHeaders = $this->makeUniqueHeaders($headerRow);
        $outputLines = [implode(';', $uniqueHeaders)];

        foreach ($lines as $line) {
            $cols = str_getcsv($line, ';');
            $cols = array_map(fn ($c) => $this->usunSpacjeWTysiacach($c), $cols);
            $outputLines[] = implode(';', $cols);
        }

        $dir = storage_path('app/syto');
        if (! File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
        }
        $nextNum = $this->nextSytoNumber($dir);
        $filename = sprintf('syto%04d.csv', $nextNum);
        $content = implode("\n", $outputLines);
        File::put($dir . '/' . $filename, $content);

        return [$outputLines, $filename];
    }

    /** Usuwa spacje oddzielające tysiące/miliony w liczbach (np. "24 668,01" -> "24668,01"). */
    private function usunSpacjeWTysiacach(string $value): string
    {
        $v = trim($value);
        if ($v === '') {
            return $value;
        }
        if (preg_match('/^[\d\s,.\-]+$/', $v)) {
            return str_replace(' ', '', $v);
        }
        return $value;
    }

    private function makeUniqueHeaders(array $headers): array
    {
        $counts = [];
        $result = [];
        $kolumnaNum = 0;
        foreach ($headers as $h) {
            $base = trim($h);
            if ($base === '') {
                $kolumnaNum++;
                $result[] = 'kolumna' . $kolumnaNum;
                continue;
            }
            $key = mb_strtolower($base);
            $counts[$key] = ($counts[$key] ?? 0) + 1;
            $num = $counts[$key];
            $result[] = $num > 1 ? $base . $num : $base;
        }
        return $result;
    }

    private function nextSytoNumber(string $dir): int
    {
        $files = File::glob($dir . '/syto*.csv');
        $max = 0;
        foreach ($files as $f) {
            if (preg_match('/syto(\d+)\.csv$/i', basename($f), $m)) {
                $max = max($max, (int) $m[1]);
            }
        }
        return $max + 1;
    }

    public function importPlanKontForm()
    {
        return view('ustawienia.import-plan-kont');
    }

    /**
     * Oczekiwana struktura CSV: Nr;Nazwa;Rodzaj pozycji (separator ;)
     * Grupa nie jest importowana. Rodzaj pozycji jest opcjonalny.
     * Pierwszy wiersz może być nagłówkiem.
     */
    public function importPlanKont(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'plik_csv' => 'required|file|mimes:csv,txt|max:2048',
        ], [
            'plik_csv.required' => 'Wybierz plik CSV.',
            'plik_csv.file' => 'Przekaż prawidłowy plik.',
            'plik_csv.mimes' => 'Plik musi mieć rozszerzenie .csv',
        ]);

        if ($validator->fails()) {
            return redirect()->route('import-plan-kont.index')
                ->withErrors($validator)
                ->withInput();
        }

        $file = $request->file('plik_csv');
        $path = $file->getRealPath();
        $lines = $this->readCsvLines($path);

        if (empty($lines)) {
            return redirect()->route('import-plan-kont.index')
                ->with('error', 'Plik CSV jest pusty.');
        }

        $rows = [];
        $isFirst = true;

        foreach ($lines as $line) {
            $cols = str_getcsv($line, ';');
            if (count($cols) < 2) {
                continue;
            }
            $nr = trim($cols[0] ?? '');
            $nazwa = trim($cols[1] ?? '');
            $rodzaj = trim($cols[2] ?? '');

            if ($isFirst && $this->czyNaglowekPlanKont($nr, $nazwa, $rodzaj)) {
                $isFirst = false;
                continue;
            }
            $isFirst = false;

            $rows[] = [
                'nr' => $nr ?: null,
                'grupa' => null,
                'nazwa' => $nazwa ?: null,
                'rodzaj_pozycji' => $rodzaj ?: null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (empty($rows)) {
            return redirect()->route('import-plan-kont.index')
                ->with('error', 'W pliku nie znaleziono wierszy z danymi. Sprawdź strukturę: Nr;Nazwa;Rodzaj pozycji (separator średnik). Rodzaj pozycji jest opcjonalny.');
        }

        try {
            DB::transaction(function () use ($rows) {
                PlanKont::insert($rows);
            });
        } catch (\Throwable $e) {
            return redirect()->route('import-plan-kont.index')
                ->with('error', 'Błąd importu: ' . $e->getMessage());
        }

        $count = count($rows);
        return redirect()->route('import-plan-kont.index')
            ->with('success', "Zaimportowano {$count} pozycji do planu kont.");
    }

    /**
     * Import danych: CSV z kolumnami Nr;Grupa;Nazwa;Rodzaj pozycji;wn1;ma1;wn;ma (separator ;).
     * Pierwszy wiersz musi być nagłówkiem. Wymagana data okresu.
     */
    public function importDanych(Request $request)
    {
        try {
            return $this->importDanychHandle($request);
        } catch (\Throwable $e) {
            return redirect()->route('import.index')
                ->with('error', 'Błąd importu: ' . $e->getMessage());
        }
    }

    private function importDanychHandle(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'plik_csv' => 'required|file|mimes:csv,txt|max:20480',
            'rok' => 'required|integer|min:2000|max:2100',
            'miesiac' => 'required|in:01,02,03,04,05,06,07,08,09,10,11,12',
        ], [
            'plik_csv.required' => 'Wybierz plik CSV.',
            'plik_csv.file' => 'Przekaż prawidłowy plik.',
            'plik_csv.mimes' => 'Plik musi mieć rozszerzenie .csv lub .txt.',
            'rok.required' => 'Wybierz rok.',
            'miesiac.required' => 'Wybierz miesiąc.',
            'miesiac.in' => 'Wybierz prawidłowy miesiąc.',
        ]);

        if ($validator->fails()) {
            return redirect()->route('import.create')
                ->withErrors($validator)
                ->withInput();
        }

        $file = $request->file('plik_csv');
        $path = $file->getRealPath();

        [$outputLines, $filename] = $this->przerobPlikCsv($path);

        if (empty($outputLines)) {
            return redirect()->route('import.create')
                ->with('error', 'Plik CSV jest pusty.');
        }

        $dataOkresu = \Carbon\Carbon::createFromDate(
            (int) $request->input('rok'),
            (int) $request->input('miesiac'),
            1
        )->endOfMonth();

        $existingImport = Import::whereYear('data_okresu', (int) $request->input('rok'))
            ->whereMonth('data_okresu', (int) $request->input('miesiac'))
            ->withCount('dane')
            ->first();

        $headerRow = str_getcsv(array_shift($outputLines), ';');
        $headerLower = array_map(fn ($c) => mb_strtolower(trim($c)), $headerRow);

        $idxNr = $this->findColumnIndex($headerLower, ['nr']);
        $idxWn4 = $this->findColumnIndex($headerLower, ['wn4']);
        $idxMa4 = $this->findColumnIndex($headerLower, ['ma4']);
        $idxWn5 = $this->findColumnIndex($headerLower, ['wn5']);
        $idxMa5 = $this->findColumnIndex($headerLower, ['ma5']);

        $brakujace = [];
        if ($idxNr === null) {
            $brakujace[] = 'nr';
        }
        if ($idxWn4 === null) {
            $brakujace[] = 'wn4';
        }
        if ($idxMa4 === null) {
            $brakujace[] = 'ma4';
        }
        if ($idxWn5 === null) {
            $brakujace[] = 'wn5';
        }
        if ($idxMa5 === null) {
            $brakujace[] = 'ma5';
        }
        if (! empty($brakujace)) {
            return redirect()->route('import.create')
                ->with('error', 'Brak wymaganych kolumn: ' . implode(', ', $brakujace) . '. Oczekiwane po przeróbce: Nr, wn4, ma4, wn5, ma5 (np. WN, MA, WN, MA... → WN1, MA1, WN2, MA2, WN3, MA3, WN4, MA4, WN5, MA5).');
        }

        $rows = [];
        foreach ($outputLines as $line) {
            $cols = str_getcsv($line, ';');
            $nr = trim($cols[$idxNr] ?? '');
            if ($nr === '') {
                continue;
            }
            $rows[] = [
                'nr' => $nr ?: null,
                'wn4' => $this->parseAmount($cols[$idxWn4] ?? ''),
                'ma4' => $this->parseAmount($cols[$idxMa4] ?? ''),
                'wn5' => $this->parseAmount($cols[$idxWn5] ?? ''),
                'ma5' => $this->parseAmount($cols[$idxMa5] ?? ''),
            ];
        }

        if (empty($rows)) {
            return redirect()->route('import.create')
                ->with('error', 'W pliku nie znaleziono wierszy z danymi.');
        }

        $nryWImporcie = array_unique(array_filter(array_map(fn ($r) => $r['nr'], $rows)));
        $nryWPlanieKont = PlanKont::pluck('nr')->filter()->unique()->values()->all();
        $brakujaceKonta = array_values(array_diff($nryWImporcie, $nryWPlanieKont));

        if (! empty($brakujaceKonta)) {
            try {
                session([
                    'pending_import' => [
                        'rows' => $rows,
                        'data_okresu' => $dataOkresu->format('Y-m-d'),
                        'nazwa_pliku' => $filename,
                    ],
                    'missing_konta' => $brakujaceKonta,
                ]);
            } catch (\Throwable $e) {
                return redirect()->route('import.index')
                    ->with('error', 'Za dużo danych do potwierdzenia (' . count($rows) . ' wierszy). Import anulowany. Spróbuj zaimportować mniejszy plik lub dodaj brakujące konta do planu kont i zaimportuj ponownie.');
            }

            return redirect()->route('import.index')
                ->with('brakujace_konta', $brakujaceKonta);
        }

        if ($existingImport && ! $request->boolean('replace_existing')) {
            session([
                'import_exists' => [
                    'id' => $existingImport->id,
                    'okres_nazwa' => $existingImport->okres_nazwa,
                    'created_at' => $existingImport->created_at->format('Y-m-d H:i'),
                    'dane_count' => $existingImport->dane_count,
                ],
                'import_replacement_pending' => [
                    'rows' => $rows,
                    'data_okresu' => $dataOkresu->format('Y-m-d'),
                    'nazwa_pliku' => $filename,
                ],
            ]);
            return redirect()->route('import.index');
        }

        if ($existingImport) {
            $existingImport->delete();
        }

        return $this->wykonajImportDanych($rows, $dataOkresu, $filename);
    }

    public function importDanychZastap(Request $request)
    {
        $request->validate(['potwierdz' => 'required|in:1']);

        $existing = session('import_exists');
        $pending = session('import_replacement_pending');

        if (! $existing || ! $pending || empty($pending['rows'])) {
            session()->forget(['import_exists', 'import_replacement_pending']);
            return redirect()->route('import.index')
                ->with('error', 'Brak oczekującego potwierdzenia. Rozpocznij import ponownie.');
        }

        $importToDelete = Import::find($existing['id']);
        if ($importToDelete) {
            $importToDelete->delete();
        }

        session()->forget(['import_exists', 'import_replacement_pending']);

        $dataOkresu = \Carbon\Carbon::parse($pending['data_okresu']);
        $dir = storage_path('app/syto');
        if (! File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
        }
        $filename = $pending['nazwa_pliku'] ?? sprintf('syto%04d.csv', $this->nextSytoNumber($dir));
        return $this->wykonajImportDanych($pending['rows'], $dataOkresu, $filename);
    }

    public function anulujImportZastap()
    {
        session()->forget(['import_exists', 'import_replacement_pending']);
        return redirect()->route('import.index');
    }

    public function importDanychPotwierdz(Request $request)
    {
        $request->validate(['potwierdz' => 'required|in:1']);

        $pending = session('pending_import');
        $missingKonta = session('missing_konta');

        if (! $pending || empty($pending['rows'])) {
            session()->forget(['pending_import', 'missing_konta']);
            return redirect()->route('import.index')
                ->with('error', 'Brak oczekującego importu. Rozpocznij import ponownie.');
        }

        session()->forget(['pending_import', 'missing_konta']);

        $dataOkresu = \Carbon\Carbon::parse($pending['data_okresu']);
        return $this->wykonajImportDanych($pending['rows'], $dataOkresu, $pending['nazwa_pliku']);
    }

    private function wykonajImportDanych(array $rows, $dataOkresu, string $nazwaPliku)
    {
        try {
            DB::transaction(function () use ($rows, $dataOkresu, $nazwaPliku) {
                $import = Import::create([
                    'data_okresu' => $dataOkresu,
                    'nazwa_pliku' => $nazwaPliku,
                ]);
                foreach ($rows as $row) {
                    $import->dane()->create($row);
                }
            });
        } catch (\Throwable $e) {
            return redirect()->route('import.index')
                ->with('error', 'Błąd importu: ' . $e->getMessage());
        }

        $count = count($rows);
        return redirect()->route('import.index')
            ->with('success', "Zaimportowano {$count} wierszy danych dla okresu " . $dataOkresu->format('Y-m') . '.');
    }

    public function anulujPendingImport()
    {
        session()->forget(['pending_import', 'missing_konta']);
        return redirect()->route('import.index');
    }

    public function destroy(Import $import)
    {
        $import->delete();
        return redirect()->route('import.index')->with('success', 'Import został usunięty.');
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

    private function parseAmount(string $value): float
    {
        $value = trim(str_replace(',', '.', $value));
        return $value === '' ? 0.0 : (float) $value;
    }

    private function czyNaglowek(string $nr, string $grupa, string $nazwa, string $rodzaj): bool
    {
        $nr = mb_strtolower($nr);
        $grupa = mb_strtolower($grupa);
        $nazwa = mb_strtolower($nazwa);
        return ($nr === 'nr' && $grupa === 'grupa') || ($nazwa === 'nazwa' && (str_contains($rodzaj, 'rodzaj') || $rodzaj === ''));
    }

    private function czyNaglowekPlanKont(string $nr, string $nazwa, string $rodzaj): bool
    {
        $nr = mb_strtolower($nr);
        $nazwa = mb_strtolower($nazwa);
        $rodzaj = mb_strtolower($rodzaj);
        return ($nr === 'nr' && $nazwa === 'nazwa') || ($nr === 'konto' && $nazwa === 'nazwa') || ($nazwa === 'nazwa' && (str_contains($rodzaj, 'rodzaj') || $rodzaj === ''));
    }

    /**
     * Odczytuje linie CSV z poprawką kodowania (Windows-1250/CP1250 dla polskich znaków).
     */
    private function readCsvLines(string $path): array
    {
        $rawContent = file_get_contents($path);
        $lines = preg_split('/\r\n|\r|\n/', $rawContent);
        $lines = array_values(array_filter(array_map('trim', $lines), fn ($l) => $l !== ''));

        if (empty($lines)) {
            return [];
        }

        $content = implode("\n", $lines);
        if (mb_check_encoding($content, 'UTF-8')) {
            return $lines;
        }

        foreach (['Windows-1250', 'CP1250', 'ISO-8859-2'] as $enc) {
            $converted = [];
            $ok = true;
            foreach ($lines as $line) {
                $conv = @iconv($enc, 'UTF-8', $line);
                if ($conv === false) {
                    $ok = false;
                    break;
                }
                $converted[] = $conv;
            }
            if ($ok && ! empty($converted) && mb_check_encoding(implode('', $converted), 'UTF-8')) {
                return $converted;
            }
        }

        return $lines;
    }
}
