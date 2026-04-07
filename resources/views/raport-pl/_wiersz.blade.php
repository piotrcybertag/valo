@php
    $klasa = trim((!empty($w['pogrubiony']) && !isset($w['grupa_idx']) ? 'raport-pl-pogrubiony ' : '') . (isset($w['grupa_idx']) ? 'raport-pl-grupa-details raport-pl-grupa-details-' . $w['grupa_idx'] . ' raport-pl-grupa-ukryta' : ''));
    $sumaRight = in_array($w['kategoria'] ?? '', ['Margin1 (suma ze wszystkich grup)', 'Direct (suma ze wszystkich grup)'], true);
    if ($sumaRight) { $klasa .= ' raport-pl-suma-row'; }
    $marginRight = isset($w['grupa_idx']) && ($w['kategoria'] ?? '') === 'Margin1';
    $ebitRight = ($w['kategoria'] ?? '') === 'EBIT';
    $incomeRight = ($w['kategoria'] ?? '') === 'Income';
    $incomeAdjustedRight = ($w['kategoria'] ?? '') === 'Income Adjusted';
    $plan = $w['plan'] ?? null;
    $miesiac = $miesiac ?? 12;
    $pctTotal = ($plan !== null && $plan != 0) ? (($w['narastajaco'] ?? 0) / $plan) * 100 : null;
    $pctBiezacy = ($plan !== null && $plan != 0 && $miesiac > 0) ? (($w['narastajaco'] ?? 0) / ($plan * $miesiac / 12)) * 100 : null;
@endphp
@php
    $pozycje = $w['pozycje'] ?? [];
    $summaryRowStyle = in_array($w['kategoria'] ?? '', ['Indirect', 'Financial', 'Koszty ogólne Direct (bez grupy)'], true);
@endphp
@if(!empty($pozycje) || $summaryRowStyle)
<tr class="{{ $klasa }}">
    <td style="{{ $marginRight ? 'display:flex; justify-content:space-between; align-items:center; padding-left:1.5rem;' : ($summaryRowStyle ? 'display:flex; justify-content:flex-start; align-items:center;' : 'padding-left:1.5rem;') }}">
        <span>
            <span class="raport-pl-expand" role="button" tabindex="0" onclick="document.getElementById('raport-pl-details-{{ $loop->index }}').classList.toggle('raport-pl-pozycje-widoczne'); this.textContent = this.textContent === '>' ? 'v' : '>'" title="Pokaż pozycje">&gt;</span>
            @if(!$marginRight){{ $w['kategoria'] }}{{ ($w['kategoria'] ?? '') === 'Margin1' ? ' (Sales - CoS)' : '' }}@endif
        </span>
        @if($marginRight)<span>{{ $w['kategoria'] }} (Sales - CoS)</span>@endif
    </td>
    <td class="text-right">{{ $w['plan'] !== null ? number_format($w['plan'], 2, ',', ' ') : '—' }}</td>
    <td class="text-right">{{ $pctBiezacy !== null ? number_format($pctBiezacy, 1, ',', ' ') . '%' : '—' }}</td>
    <td class="text-right">{{ number_format($w['narastajaco'], 2, ',', ' ') }}</td>
    @for($m = $miesiac; $m >= 1; $m--)
    <td class="text-right">
        @php $val = $w['wartosci_miesieczne'][$m] ?? null; @endphp
        @if($val !== null)
            @if($m === $miesiac && !empty($w['pogrubiony']))<strong>@endif{{ number_format($val, 2, ',', ' ') }}@if($m === $miesiac && !empty($w['pogrubiony']))</strong>@endif
        @else
            —
        @endif
    </td>
    @endfor
</tr>
<tr id="raport-pl-details-{{ $loop->index }}" class="raport-pl-pozycje-row {{ isset($w['grupa_idx']) ? 'raport-pl-grupa-details raport-pl-grupa-details-' . $w['grupa_idx'] . ' raport-pl-grupa-ukryta' : '' }}">
    <td colspan="{{ 4 + $miesiac }}" class="raport-pl-pozycje-cell">
        <div class="raport-pl-pozycje-list">
            <table class="raport-pl-pozycje-table">
                <thead>
                    <tr>
                        <th>Nr</th>
                        <th>Nazwa</th>
                        @for($m = $miesiac; $m >= 1; $m--)
                        <th class="text-right">{{ ['Styczeń','Luty','Marzec','Kwiecień','Maj','Czerwiec','Lipiec','Sierpień','Wrzesień','Październik','Listopad','Grudzień'][$m-1] }}</th>
                        @endfor
                    </tr>
                </thead>
                <tbody>
                    @foreach($pozycje as $p)
                    <tr>
                        <td>{{ $p['nr'] }}</td>
                        <td>{{ $p['nazwa'] }}</td>
                        @for($m = $miesiac; $m >= 1; $m--)
                        <td class="text-right">
                            @php $val = $p['wartosci_miesieczne'][$m] ?? null; @endphp
                            @if($val !== null)
                                {{ number_format($val, 2, ',', ' ') }}
                            @else
                                —
                            @endif
                        </td>
                        @endfor
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </td>
</tr>
@else
<tr class="{{ $klasa }}">
    <td style="padding-left:1.5rem;{{ ($marginRight || $ebitRight || $incomeRight || $incomeAdjustedRight || $sumaRight) ? ' text-align:right;' : '' }}">{{ $w['kategoria'] }}{{ ($w['kategoria'] ?? '') === 'Margin1' ? ' (Sales - CoS)' : '' }}{{ $ebitRight ? ' (Operational Result - Indirect)' : '' }}{{ $incomeRight ? ' (EBIT - Financial)' : '' }}{{ $incomeAdjustedRight ? ' (Income + WIP)' : '' }}</td>
    <td class="text-right">{{ $w['plan'] !== null ? number_format($w['plan'], 2, ',', ' ') : '—' }}</td>
    <td class="text-right">{{ $pctBiezacy !== null ? number_format($pctBiezacy, 1, ',', ' ') . '%' : '—' }}</td>
    <td class="text-right">{{ number_format($w['narastajaco'], 2, ',', ' ') }}</td>
    @for($m = $miesiac; $m >= 1; $m--)
    <td class="text-right">
        @php $val = $w['wartosci_miesieczne'][$m] ?? null; @endphp
        @if($val !== null)
            @if($m === $miesiac && !empty($w['pogrubiony']))<strong>@endif{{ number_format($val, 2, ',', ' ') }}@if($m === $miesiac && !empty($w['pogrubiony']))</strong>@endif
        @else
            —
        @endif
    </td>
    @endfor
</tr>
@endif
