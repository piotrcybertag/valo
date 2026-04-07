@extends('layouts.app')

@section('title', 'Piątki – przegląd – ' . config('app.name'))

@section('content')
<div class="page-content page-content--wide">
    <div class="page-header">
        <h1 class="page-title">Piątki – przegląd danych</h1>
        <a href="{{ route('piatki.index') }}" class="btn btn-outline">← Powrót do listy</a>
    </div>

    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Import: {{ $importProjekt->nazwa_pliku ?? '—' }}, zaimportowano {{ $importProjekt->created_at->format('Y-m-d H:i') }}, {{ $importProjekt->dane->count() }} wierszy.</p>

    @php
        $grupy = [];
        foreach ($importProjekt->dane as $d) {
            $nazwa = $d->nazwa ?? '';
            if (!isset($grupy[$nazwa])) {
                $grupy[$nazwa] = ['wn3' => 0.0, 'ma3' => 0.0, 'szczegoly' => []];
            }
            $w = $d->wartosci ?? [];
            $wn3 = (float) ($w['wn3'] ?? 0);
            $ma3 = (float) ($w['ma3'] ?? 0);
            $grupy[$nazwa]['wn3'] += $wn3;
            $grupy[$nazwa]['ma3'] += $ma3;
            $grupy[$nazwa]['szczegoly'][] = ['nr' => $d->nr, 'wn3' => $wn3, 'ma3' => $ma3];
        }
    @endphp

    <style>
        .piatki-expand { display:inline-block; width:1.25rem; cursor:pointer; font-weight:700; color:#1e3a5f; user-select:none; }
        .piatki-expand:hover { color:#16304d; }
        .piatki-details-row { display:none; }
        .piatki-details-row.piatki-details-widoczne { display:table-row; }
        .piatki-details-cell { padding:0.5rem 1rem 1rem 3rem !important; background:#f9fafb; font-size:.75rem; }
        .piatki-details-table { width:100%; border-collapse:collapse; font-size:.75rem; }
        .piatki-details-table th, .piatki-details-table td { padding:.25rem .5rem; text-align:left; border:none; }
        .piatki-details-table th { font-weight:600; color:#6b7280; }
        .piatki-num { font-family: ui-monospace, 'Cascadia Code', 'Source Code Pro', Menlo, Consolas, monospace; }
        .piatki-wynik-ujemny { background-color: #fed7aa !important; }
        .piatki-margin-wysoki { background-color: #bbf7d0 !important; }
    </style>

    <div class="table-wrap overflow-x-auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Nazwa</th>
                    <th class="text-right">Przychód</th>
                    <th class="text-right">Koszt</th>
                    <th class="text-right">Wynik</th>
                    <th class="text-right">% margin</th>
                </tr>
            </thead>
            <tbody>
                @foreach($grupy as $nazwa => $grupa)
                @php
                    $przychod = $grupa['ma3'];
                    $koszt = $grupa['wn3'];
                    $wynik = $przychod - $koszt;
                    $pctMargin = $przychod != 0 ? round(($wynik / $przychod) * 100, 1) : null;
                @endphp
                <tr>
                    <td>
                        @if(count($grupa['szczegoly']) > 0)
                        <span class="piatki-expand" role="button" tabindex="0" onclick="document.getElementById('piatki-details-{{ $loop->index }}').classList.toggle('piatki-details-widoczne'); this.textContent = this.textContent === '>' ? 'v' : '>'" title="Pokaż szczegóły">&gt;</span>
                        @endif
                        {{ $nazwa }}
                    </td>
                    <td class="text-right piatki-num">{{ number_format($przychod, 2, ',', ' ') }}</td>
                    <td class="text-right piatki-num">{{ number_format($koszt, 2, ',', ' ') }}</td>
                    <td class="text-right piatki-num {{ $wynik < 0 ? 'piatki-wynik-ujemny' : '' }}">{{ number_format($wynik, 2, ',', ' ') }}</td>
                    <td class="text-right piatki-num {{ $pctMargin !== null && $pctMargin > 50 ? 'piatki-margin-wysoki' : '' }}">{{ $pctMargin !== null ? number_format($pctMargin, 1, ',', ' ') . '%' : '—' }}</td>
                </tr>
                <tr id="piatki-details-{{ $loop->index }}" class="piatki-details-row">
                    <td colspan="5" class="piatki-details-cell">
                        <table class="piatki-details-table">
                            <thead><tr><th>Nr</th><th class="text-right">Przychód</th><th class="text-right">Koszt</th><th class="text-right">Wynik</th><th class="text-right">% margin</th></tr></thead>
                            <tbody>
                                @foreach($grupa['szczegoly'] as $s)
                                @php
                                    $sp = $s['ma3'];
                                    $sk = $s['wn3'];
                                    $sw = $sp - $sk;
                                    $spct = $sp != 0 ? round(($sw / $sp) * 100, 1) : null;
                                @endphp
                                <tr>
                                    <td>{{ $s['nr'] }}</td>
                                    <td class="text-right piatki-num">{{ number_format($sp, 2, ',', ' ') }}</td>
                                    <td class="text-right piatki-num">{{ number_format($sk, 2, ',', ' ') }}</td>
                                    <td class="text-right piatki-num {{ $sw < 0 ? 'piatki-wynik-ujemny' : '' }}">{{ number_format($sw, 2, ',', ' ') }}</td>
                                    <td class="text-right piatki-num {{ $spct !== null && $spct > 50 ? 'piatki-margin-wysoki' : '' }}">{{ $spct !== null ? number_format($spct, 1, ',', ' ') . '%' : '—' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
