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
    <td class="text-right">@if(!empty($w['pogrubiony']))<strong>@endif{{ number_format($w['narastajaco'], 2, ',', ' ') }}@if(!empty($w['pogrubiony']))</strong>@endif</td>
    <td class="text-right">@if(!empty($w['pogrubiony']))<strong>@endif{{ number_format($w['biezacy'] ?? 0, 2, ',', ' ') }}@if(!empty($w['pogrubiony']))</strong>@endif</td>
    <td class="text-right">@if(!empty($w['pogrubiony']))<strong>@endif{{ number_format($w['poprzednie_okresy'] ?? 0, 2, ',', ' ') }}@if(!empty($w['pogrubiony']))</strong>@endif</td>
</tr>
<tr id="raport-pl-details-{{ $loop->index }}" class="raport-pl-pozycje-row {{ isset($w['grupa_idx']) ? 'raport-pl-grupa-details raport-pl-grupa-details-' . $w['grupa_idx'] . ' raport-pl-grupa-ukryta' : '' }}">
    <td colspan="6" class="raport-pl-pozycje-cell">
        <div class="raport-pl-pozycje-list">
            <table class="raport-pl-pozycje-table">
                <thead>
                    <tr>
                        <th>Nr</th>
                        <th>Nazwa</th>
                        <th class="text-right">Narastająco</th>
                        <th class="text-right">Bieżący</th>
                        <th class="text-right">Poprzednie okresy</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($pozycje as $p)
                    <tr>
                        <td>{{ $p['nr'] }}</td>
                        <td>{{ $p['nazwa'] }}</td>
                        <td class="text-right">{{ number_format($p['narastajaco'] ?? 0, 2, ',', ' ') }}</td>
                        <td class="text-right">{{ number_format($p['biezacy'] ?? 0, 2, ',', ' ') }}</td>
                        <td class="text-right">{{ number_format($p['poprzednie_okresy'] ?? 0, 2, ',', ' ') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </td>
</tr>
@else
<tr class="{{ $klasa }}">
    <td style="padding-left:1.5rem;{{ ($marginRight || $ebitRight || $incomeRight || $incomeAdjustedRight || $sumaRight) ? ' text-align:right;' : '' }}">{{ $w['kategoria'] }}{{ ($w['kategoria'] ?? '') === 'Niezadekretowane' ? ' (ND)' : '' }}{{ ($w['kategoria'] ?? '') === 'Margin1' ? ' (Sales - CoS)' : '' }}{{ $ebitRight ? ' (Operational Result - Indirect)' : '' }}{{ $incomeRight ? ' (EBIT - Financial)' : '' }}{{ $incomeAdjustedRight ? ' (Income + WIP − ND)' : '' }}</td>
    <td class="text-right">{{ $w['plan'] !== null ? number_format($w['plan'], 2, ',', ' ') : '—' }}</td>
    <td class="text-right">{{ $pctBiezacy !== null ? number_format($pctBiezacy, 1, ',', ' ') . '%' : '—' }}</td>
    <td class="text-right">@if(!empty($w['pogrubiony']))<strong>@endif{{ number_format($w['narastajaco'], 2, ',', ' ') }}@if(!empty($w['pogrubiony']))</strong>@endif</td>
    <td class="text-right">@if(!empty($w['pogrubiony']))<strong>@endif{{ number_format($w['biezacy'] ?? 0, 2, ',', ' ') }}@if(!empty($w['pogrubiony']))</strong>@endif</td>
    <td class="text-right">@if(!empty($w['pogrubiony']))<strong>@endif{{ number_format($w['poprzednie_okresy'] ?? 0, 2, ',', ' ') }}@if(!empty($w['pogrubiony']))</strong>@endif</td>
</tr>
@endif
