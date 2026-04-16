@extends('layouts.app')

@section('title', 'Podgląd importu finansowego – ' . config('app.name'))

@section('content')
    <div class="page-content page-content--wide">
        <div class="page-header">
            <h1 class="page-title">Podgląd przerobionego pliku</h1>
        </div>

        @if (session('error'))
            <div class="mb-4 p-4 rounded-lg alert-danger">{{ session('error') }}</div>
        @endif

        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
            Okres importu: <strong>{{ $preview['okres_nazwa'] ?? '—' }}</strong>.
            Liczba wierszy danych (kont): <strong>{{ count($preview['rows'] ?? []) }}</strong>.
            @if (array_key_exists('niezadekretowane', $preview))
                Suma z kolumny „Razem netto” w pliku niezadekretowanych (zapisze się w imporcie): <strong>{{ number_format((float) $preview['niezadekretowane'], 2, ',', ' ') }}</strong>.
            @endif
            Poniżej treść pliku SiO po przeróbce (nagłówki unikalne, spacje w liczbach usunięte). Sprawdź dane i zatwierdź import lub anuluj.
        </p>

        @if (($preview['istniejace_importy_dla_okresu'] ?? 0) > 0)
            <div class="mb-4 p-4 rounded-lg bg-[#f0f9ff] dark:bg-[#1e293b] border border-[#bae6fd] dark:border-slate-600 text-sm">
                <p class="font-medium mb-1">W bazie jest już {{ $preview['istniejace_importy_dla_okresu'] }} import(ów) dla tego okresu.</p>
                <p class="text-gray-600 dark:text-gray-300">Zapis doda <strong>kolejny</strong> import — poprzednie pozostają. Raport P&L dotyczy wybranego importu (data i godzina zapisu na liście).</p>
            </div>
        @endif

        @if (!empty($preview['missing_konta']))
            @php $brak = $preview['missing_konta']; @endphp
            <div class="alert-warning mb-4">
                <p class="font-medium mb-2">Konta z pliku, których nie ma w planie kont ({{ count($brak) }}):</p>
                <p class="text-sm mb-3" style="word-break:break-word;">
                    {{ count($brak) > 40 ? implode(', ', array_slice($brak, 0, 40)) . '…' : implode(', ', $brak) }}
                </p>
                <p class="text-sm mb-2">Zaznacz poniżej, jeśli chcesz <strong>najpierw dopisać te konta do planu kont</strong> (nazwa, grupa i rodzaj pozycji z pliku — jeśli są w kolumnach), a następnie wykonać import.</p>
            </div>
        @endif

        <div class="table-wrap overflow-x-auto mb-6" style="max-height:28rem;overflow-y:auto;border:1px solid #e5e7eb;border-radius:.5rem;">
            <table class="data-table" style="font-size:.75rem;">
                <tbody>
                    @foreach($lines as $lineIdx => $line)
                        @php $komorki = str_getcsv($line, ';'); @endphp
                        <tr class="{{ $lineIdx === 0 ? 'font-semibold bg-[#f3f4f6]' : '' }}">
                            @foreach($komorki as $kom)
                                <td class="whitespace-nowrap">{{ $kom }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <form action="{{ route('import.dane.wykonaj') }}" method="post" class="flex flex-wrap items-center gap-3">
            @csrf
            @if (!empty($preview['missing_konta']))
                <label class="inline-flex items-center gap-2 text-sm">
                    <input type="checkbox" name="mimo_brakujacych_kont" value="1" class="rounded border-gray-300">
                    <span>Rozumiem — dopisz brakujące konta do planu kont z pliku i wykonaj import</span>
                </label>
            @endif
            <button type="submit" class="btn btn-primary">Zapisz w bazie</button>
            <a href="{{ route('import.dane.anuluj-podglad') }}" class="btn btn-outline">Anuluj</a>
        </form>
    </div>
@endsection
