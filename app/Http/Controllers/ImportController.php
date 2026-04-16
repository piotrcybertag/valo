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

    /**
     * Przerabia plik CSV: unikalne nagłówki, usunięcie spacji w liczbach. Zwraca linie (bez zapisu na dysk).
     *
     * @return list<string>
     */
    private function przerobPlikCsvLinie(string $path): array
    {
        $lines = $this->readCsvLines($path);
        if (empty($lines)) {
            return [];
        }

        $headerRow = str_getcsv(array_shift($lines), ';');
        $uniqueHeaders = $this->makeUniqueHeaders($headerRow);
        $outputLines = [implode(';', $uniqueHeaders)];

        foreach ($lines as $line) {
            $cols = str_getcsv($line, ';');
            $cols = array_map(fn ($c) => $this->usunSpacjeWTysiacach($c), $cols);
            $outputLines[] = implode(';', $cols);
        }

        return $outputLines;
    }

    /** Zapisuje przerobiony CSV w storage/app/syto. Zwraca nazwę pliku (np. syto0001.csv). */
    private function zapiszPrzerobionyPlikSyto(array $outputLines): string
    {
        $dir = storage_path('app/syto');
        if (! File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
        }
        $filename = sprintf('syto%04d.csv', $this->nextSytoNumber($dir));
        File::put($dir . '/' . $filename, implode("\n", $outputLines));

        return $filename;
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
     * Import danych finansowych (separator ;). Po przeróbce wymagane kolumny m.in. Nr, wn2, ma2 (bieżący), wn3, ma3 (narastająco).
     * Pierwszy wiersz musi być nagłówkiem. Wymagana data okresu.
     */
    public function importDanych(Request $request)
    {
        try {
            return $this->importDanychPrzetworz($request);
        } catch (\Throwable $e) {
            return redirect()->route('import.index')
                ->with('error', 'Błąd importu: ' . $e->getMessage());
        }
    }

    /** Krok 1: walidacja, przeróbka CSV, zapis podglądu w sesji — bez zapisu do bazy. */
    private function importDanychPrzetworz(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'plik_csv' => 'required|file|mimes:csv,txt|max:20480',
            'plik_niezadekretowane' => 'required|file|mimes:csv,txt|max:20480',
            'rok' => 'required|integer|min:2000|max:2100',
            'miesiac' => 'required|in:01,02,03,04,05,06,07,08,09,10,11,12',
        ], [
            'plik_csv.required' => 'Wybierz plik zestawienia SiO (CSV).',
            'plik_csv.file' => 'Przekaż prawidłowy plik.',
            'plik_csv.mimes' => 'Plik musi mieć rozszerzenie .csv lub .txt.',
            'plik_niezadekretowane.required' => 'Wybierz plik niezadekretowanych (CSV).',
            'plik_niezadekretowane.file' => 'Przekaż prawidłowy plik niezadekretowanych.',
            'plik_niezadekretowane.mimes' => 'Plik niezadekretowanych musi mieć rozszerzenie .csv lub .txt.',
            'rok.required' => 'Wybierz rok.',
            'miesiac.required' => 'Wybierz miesiąc, którego dotyczy bieżący okres.',
            'miesiac.in' => 'Wybierz prawidłowy miesiąc.',
        ]);

        if ($validator->fails()) {
            return redirect()->route('import.create')
                ->withErrors($validator)
                ->withInput();
        }

        $file = $request->file('plik_csv');
        $path = $file->getRealPath();

        $fileNz = $request->file('plik_niezadekretowane');
        $pathNz = $fileNz->getRealPath();
        $wynikNz = $this->sumujWartosciPlikuNiezadekretowanych($pathNz);
        if (($wynikNz['error'] ?? null) !== null) {
            return redirect()->route('import.create')
                ->with('error', $wynikNz['error']);
        }
        $sumaNiezadekretowane = $wynikNz['sum'];

        $outputLines = $this->przerobPlikCsvLinie($path);

        if (empty($outputLines)) {
            return redirect()->route('import.create')
                ->with('error', 'Plik CSV jest pusty.');
        }

        $dataOkresu = \Carbon\Carbon::createFromDate(
            (int) $request->input('rok'),
            (int) $request->input('miesiac'),
            1
        )->endOfMonth();

        $istniejaceImportyDlaOkresu = Import::whereYear('data_okresu', (int) $request->input('rok'))
            ->whereMonth('data_okresu', (int) $request->input('miesiac'))
            ->count();

        $linesForParse = $outputLines;
        $headerRow = str_getcsv(array_shift($linesForParse), ';');
        $headerLower = array_map(fn ($c) => mb_strtolower(trim($c)), $headerRow);

        $idxNr = $this->findColumnIndex($headerLower, ['nr']);
        $idxNazwa = $this->findColumnIndex($headerLower, ['nazwa']);
        $idxGrupa = $this->findColumnIndex($headerLower, ['grupa']);
        $idxRodzaj = $this->findColumnIndex($headerLower, ['rodzaj pozycji', 'rodzaj']);
        $idxWn2 = $this->findColumnIndex($headerLower, ['wn2']);
        $idxMa2 = $this->findColumnIndex($headerLower, ['ma2']);
        $idxWn3 = $this->findColumnIndex($headerLower, ['wn3']);
        $idxMa3 = $this->findColumnIndex($headerLower, ['ma3']);

        $brakujace = [];
        if ($idxNr === null) {
            $brakujace[] = 'nr';
        }
        if ($idxWn2 === null) {
            $brakujace[] = 'wn2';
        }
        if ($idxMa2 === null) {
            $brakujace[] = 'ma2';
        }
        if ($idxWn3 === null) {
            $brakujace[] = 'wn3';
        }
        if ($idxMa3 === null) {
            $brakujace[] = 'ma3';
        }
        if (! empty($brakujace)) {
            return redirect()->route('import.create')
                ->with('error', 'Brak wymaganych kolumn: ' . implode(', ', $brakujace) . '. Oczekiwane po przeróbce: Nr, wn2, ma2 (bieżący okres), wn3, ma3 (narastająco) — np. WN1, MA1 … WN2, MA2, WN3, MA3.');
        }

        $rows = [];
        $kontaMeta = [];
        foreach ($linesForParse as $line) {
            $cols = str_getcsv($line, ';');
            $nr = trim($cols[$idxNr] ?? '');
            if ($nr === '') {
                continue;
            }
            if (! isset($kontaMeta[$nr])) {
                $nz = $idxNazwa !== null ? trim((string) ($cols[$idxNazwa] ?? '')) : '';
                $gr = $idxGrupa !== null ? trim((string) ($cols[$idxGrupa] ?? '')) : '';
                $rj = $idxRodzaj !== null ? trim((string) ($cols[$idxRodzaj] ?? '')) : '';
                $kontaMeta[$nr] = [
                    'nazwa' => $nz,
                    'grupa' => $gr === '' ? null : $gr,
                    'rodzaj_pozycji' => $rj === '' ? null : $rj,
                ];
            }
            $rows[] = [
                'nr' => $nr ?: null,
                'wn2' => $this->parseAmount($cols[$idxWn2] ?? ''),
                'ma2' => $this->parseAmount($cols[$idxMa2] ?? ''),
                'wn3' => $this->parseAmount($cols[$idxWn3] ?? ''),
                'ma3' => $this->parseAmount($cols[$idxMa3] ?? ''),
            ];
        }

        if (empty($rows)) {
            return redirect()->route('import.create')
                ->with('error', 'W pliku nie znaleziono wierszy z danymi.');
        }

        $nryWImporcie = array_unique(array_filter(array_map(fn ($r) => $r['nr'], $rows)));
        $nryWPlanieKont = PlanKont::pluck('nr')->filter()->unique()->values()->all();
        $brakujaceKonta = array_values(array_diff($nryWImporcie, $nryWPlanieKont));

        $miesiacePl = ['', 'Styczeń', 'Luty', 'Marzec', 'Kwiecień', 'Maj', 'Czerwiec', 'Lipiec', 'Sierpień', 'Wrzesień', 'Październik', 'Listopad', 'Grudzień'];
        $okresNazwa = ($miesiacePl[(int) $dataOkresu->format('n')] ?? '') . ' ' . $dataOkresu->format('Y');

        try {
            session([
                'financial_import_preview' => [
                    'processed_lines' => $outputLines,
                    'rows' => $rows,
                    'konta_meta' => $kontaMeta,
                    'data_okresu' => $dataOkresu->format('Y-m-d'),
                    'missing_konta' => $brakujaceKonta,
                    'okres_nazwa' => $okresNazwa,
                    'istniejace_importy_dla_okresu' => $istniejaceImportyDlaOkresu,
                    'niezadekretowane' => $sumaNiezadekretowane,
                ],
            ]);
        } catch (\Throwable $e) {
            return redirect()->route('import.create')
                ->with('error', 'Za dużo danych do podglądu (' . count($rows) . ' wierszy). Spróbuj mniejszy plik.');
        }

        return redirect()->route('import.dane.podglad');
    }

    public function importDanychPodglad()
    {
        $preview = session('financial_import_preview');
        if (! $preview || empty($preview['processed_lines'])) {
            return redirect()->route('import.create')
                ->with('error', 'Brak podglądu importu. Wybierz plik i okres ponownie.');
        }

        return view('import.dane-podglad', [
            'preview' => $preview,
            'lines' => $preview['processed_lines'],
        ]);
    }

    public function importDanychAnulujPodglad()
    {
        session()->forget('financial_import_preview');

        return redirect()->route('import.create')
            ->with('success', 'Import anulowany — możesz wybrać plik ponownie.');
    }

    /** Krok 2: zapis do bazy po potwierdzeniu na ekranie podglądu. */
    public function importDanychWykonaj(Request $request)
    {
        try {
            return $this->importDanychWykonajHandle($request);
        } catch (\Throwable $e) {
            return redirect()->route('import.dane.podglad')
                ->with('error', 'Błąd importu: ' . $e->getMessage());
        }
    }

    private function importDanychWykonajHandle(Request $request)
    {
        $preview = session('financial_import_preview');
        if (! $preview || empty($preview['rows'])) {
            return redirect()->route('import.create')
                ->with('error', 'Sesja podglądu wygasła. Rozpocznij import od początku.');
        }

        $rows = $preview['rows'];
        $dataOkresu = \Carbon\Carbon::parse($preview['data_okresu']);
        $missingKonta = $preview['missing_konta'] ?? [];
        $processedLines = $preview['processed_lines'] ?? [];

        if (! empty($missingKonta) && ! $request->boolean('mimo_brakujacych_kont')) {
            return redirect()->route('import.dane.podglad')
                ->with('error', 'W pliku są konta spoza planu kont. Zaznacz zgodę na import mimo to lub anuluj i uzupełnij plan kont.');
        }

        session()->forget('financial_import_preview');

        $filename = $this->zapiszPrzerobionyPlikSyto($processedLines);

        $dopisz = $request->boolean('mimo_brakujacych_kont') && $missingKonta !== [];

        return $this->wykonajImportDanych($rows, $dataOkresu, $filename, [
            'dopisz_brakujace' => $dopisz,
            'missing_konta' => $missingKonta,
            'konta_meta' => $preview['konta_meta'] ?? [],
            'niezadekretowane' => isset($preview['niezadekretowane']) ? (float) $preview['niezadekretowane'] : null,
        ]);
    }

    /** Dodaje brakujące konta do planu kont (nazwa/grupa/rodzaj z metadanych z pliku). */
    private function dopiszBrakujaceKontaDoPlanu(array $missingNrs, array $kontaMeta): void
    {
        foreach ($missingNrs as $nr) {
            if ($nr === null || $nr === '') {
                continue;
            }
            if (PlanKont::query()->where('nr', $nr)->exists()) {
                continue;
            }
            $meta = $kontaMeta[$nr] ?? [];
            $nazwa = trim((string) ($meta['nazwa'] ?? ''));
            if ($nazwa === '') {
                $nazwa = 'Konto ' . $nr;
            }
            PlanKont::query()->create([
                'nr' => $nr,
                'grupa' => $meta['grupa'] ?? null,
                'nazwa' => $nazwa,
                'rodzaj_pozycji' => $meta['rodzaj_pozycji'] ?? null,
            ]);
        }
    }

    public function importDanychPotwierdz(Request $request)
    {
        $request->validate(['potwierdz' => 'required|in:1']);

        $pending = session('pending_import');
        $missingKonta = session('missing_konta', []);

        if (! $pending || empty($pending['rows'])) {
            session()->forget(['pending_import', 'missing_konta']);
            return redirect()->route('import.index')
                ->with('error', 'Brak oczekującego importu. Rozpocznij import ponownie.');
        }

        session()->forget(['pending_import', 'missing_konta']);

        $dataOkresu = \Carbon\Carbon::parse($pending['data_okresu']);
        $processedLines = $pending['processed_lines'] ?? null;
        if (! empty($processedLines) && is_array($processedLines)) {
            $nazwaPliku = $this->zapiszPrzerobionyPlikSyto($processedLines);
        } else {
            $nazwaPliku = $pending['nazwa_pliku'];
            if ($nazwaPliku === null || $nazwaPliku === '') {
                $dir = storage_path('app/syto');
                if (! File::isDirectory($dir)) {
                    File::makeDirectory($dir, 0755, true);
                }
                $nazwaPliku = sprintf('syto%04d.csv', $this->nextSytoNumber($dir));
            }
        }

        $missingList = is_array($missingKonta) ? $missingKonta : [];

        return $this->wykonajImportDanych($pending['rows'], $dataOkresu, $nazwaPliku, [
            'dopisz_brakujace' => $missingList !== [],
            'missing_konta' => $missingList,
            'konta_meta' => $pending['konta_meta'] ?? [],
            'niezadekretowane' => isset($pending['niezadekretowane']) ? (float) $pending['niezadekretowane'] : null,
        ]);
    }

    /**
     * @param  array{dopisz_brakujace?: bool, missing_konta?: list<string>, konta_meta?: array<string, array{nazwa?: string, grupa?: string|null, rodzaj_pozycji?: string|null}>, niezadekretowane?: float|null}  $opts
     */
    private function wykonajImportDanych(array $rows, $dataOkresu, string $nazwaPliku, array $opts = [])
    {
        $dopisz = ! empty($opts['dopisz_brakujace']) && ! empty($opts['missing_konta']);
        $missing = $opts['missing_konta'] ?? [];
        $kontaMeta = $opts['konta_meta'] ?? [];
        $niezadekretowane = array_key_exists('niezadekretowane', $opts) ? $opts['niezadekretowane'] : null;

        try {
            DB::transaction(function () use ($rows, $dataOkresu, $nazwaPliku, $dopisz, $missing, $kontaMeta, $niezadekretowane) {
                if ($dopisz) {
                    $this->dopiszBrakujaceKontaDoPlanu($missing, $kontaMeta);
                }
                $import = Import::create([
                    'data_okresu' => $dataOkresu,
                    'nazwa_pliku' => $nazwaPliku,
                    'niezadekretowane' => $niezadekretowane,
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
        $suffix = $dopisz ? ' Uzupełniono plan kont o brakujące pozycje z pliku.' : '';

        return redirect()->route('import.index')
            ->with('success', "Zaimportowano {$count} wierszy danych dla okresu " . $dataOkresu->format('Y-m') . '.' . $suffix);
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

    /**
     * Suma wartości z kolumny „Razem netto” w pliku niezadekretowanych (CSV, średnik).
     *
     * @return array{sum: float, error: string|null}
     */
    private function sumujWartosciPlikuNiezadekretowanych(string $path): array
    {
        $lines = $this->readCsvLines($path);
        if (empty($lines)) {
            return ['sum' => 0.0, 'error' => 'Plik niezadekretowanych jest pusty.'];
        }

        $headerRow = str_getcsv(array_shift($lines), ';');
        $headerLower = array_map(fn ($c) => mb_strtolower(trim((string) $c)), $headerRow);
        $idxRazemNetto = $this->findColumnIndex($headerLower, ['razem netto']);

        if ($idxRazemNetto === null) {
            return [
                'sum' => 0.0,
                'error' => 'W pliku niezadekretowanych brak kolumny „Razem netto” (pierwszy wiersz = nagłówki, separator średnik).',
            ];
        }

        $sum = 0.0;
        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }
            $cols = str_getcsv($line, ';');
            $c = trim($this->usunSpacjeWTysiacach((string) ($cols[$idxRazemNetto] ?? '')));
            if ($c === '' || $c === '-') {
                continue;
            }
            if (preg_match('/^[\d\s,.\-]+$/', $c)) {
                $sum += $this->parseAmount($c);
            }
        }

        return ['sum' => round($sum, 2), 'error' => null];
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
