@extends('layouts.app')

@section('title', 'Raport P&L – ' . ($import->okres_nazwa ?? '') . ' – ' . config('app.name'))

@section('content')
    <div class="page-content page-content--wide">
        <div class="page-header">
            <h1 class="page-title">Raport P&L</h1>
        </div>
        <div class="mb-4 flex flex-wrap items-center gap-4">
            <label for="raport-pl-okres" class="text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]">Okres:</label>
            <select id="raport-pl-okres" class="form-input w-auto min-w-[16rem]" onchange="window.location=this.value">
                @foreach($imports ?? [] as $imp)
                    <option value="{{ route('raport-pl.show', $imp) }}" {{ $imp->id === $import->id ? 'selected' : '' }}>
                        {{ $imp->okres_nazwa ?? '—' }} (import {{ $imp->created_at->format('Y-m-d H:i') }}, {{ $imp->dane_count }} wierszy)
                    </option>
                @endforeach
            </select>
        </div>
        <div class="table-wrap overflow-x-auto w-full max-w-none mb-8">
            <table class="data-table raport-pl-table">
                <thead>
                    <tr>
                        <th>Kategoria</th>
                        <th class="text-right">Plan</th>
                        <th class="text-right">% plan bieżący</th>
                        <th class="text-right">Narastająco</th>
                        <th class="text-right">Bieżący<br><span class="text-xs font-normal opacity-80">{{ $import->okres_nazwa ?? '—' }}</span></th>
                        <th class="text-right">Poprzednie okresy</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($wiersze as $w)
                    @if($w['typ'] === 'podsumowanie_sales')
                    @php
                        $plan = $w['plan'] ?? null;
                        $pctBiezacy = ($plan !== null && $plan != 0 && ($miesiac ?? 12) > 0) ? ($w['narastajaco'] / ($plan * ($miesiac ?? 12) / 12)) * 100 : null;
                    @endphp
                    <tr class="raport-pl-naglowek-grupy">
                        <td><strong>Total Sales</strong></td>
                        <td class="text-right">{{ isset($w['plan']) && $w['plan'] !== null ? number_format($w['plan'], 2, ',', ' ') : '—' }}</td>
                        <td class="text-right">{{ $pctBiezacy !== null ? number_format($pctBiezacy, 1, ',', ' ') . '%' : '—' }}</td>
                        <td class="text-right"><strong>{{ number_format($w['narastajaco'], 2, ',', ' ') }}</strong></td>
                        <td class="text-right"><strong>{{ number_format($w['biezacy'], 2, ',', ' ') }}</strong></td>
                        <td class="text-right"><strong>{{ number_format($w['poprzednie_okresy'], 2, ',', ' ') }}</strong></td>
                    </tr>
                    @elseif($w['typ'] === 'podsumowanie_oper_result')
                    @php
                        $plan = $w['plan'] ?? null;
                        $pctBiezacy = ($plan !== null && $plan != 0 && ($miesiac ?? 12) > 0) ? ($w['narastajaco'] / ($plan * ($miesiac ?? 12) / 12)) * 100 : null;
                    @endphp
                    <tr class="raport-pl-naglowek-grupy">
                        <td style="display: flex; justify-content: flex-end; align-items: center;"><strong>Total Operational Result</strong> <span class="raport-pl-oper-result-label">(Margin1 - Direct)</span></td>
                        <td class="text-right">{{ isset($w['plan']) && $w['plan'] !== null ? number_format($w['plan'], 2, ',', ' ') : '—' }}</td>
                        <td class="text-right">{{ $pctBiezacy !== null ? number_format($pctBiezacy, 1, ',', ' ') . '%' : '—' }}</td>
                        <td class="text-right"><strong>{{ number_format($w['narastajaco'], 2, ',', ' ') }}</strong></td>
                        <td class="text-right"><strong>{{ number_format($w['biezacy'], 2, ',', ' ') }}</strong></td>
                        <td class="text-right"><strong>{{ number_format($w['poprzednie_okresy'], 2, ',', ' ') }}</strong></td>
                    </tr>
                    @elseif($w['typ'] === 'naglowek_grupy')
                    @php
                        $plan = $w['oper_result_plan'] ?? null;
                        $pctBiezacy = ($plan !== null && $plan != 0 && ($miesiac ?? 12) > 0) ? (($w['oper_result_narastajaco'] ?? 0) / ($plan * ($miesiac ?? 12) / 12)) * 100 : null;
                    @endphp
                    <tr class="raport-pl-naglowek-grupy">
                        <td style="display: flex; justify-content: space-between; align-items: center;">
                            <span>
                                @if(isset($w['grupa_idx']))
                                <span class="raport-pl-expand raport-pl-grupa-expand" role="button" tabindex="0" onclick="var r=document.querySelectorAll('.raport-pl-grupa-details-{{ $w['grupa_idx'] }}'); r.forEach(function(x){x.classList.toggle('raport-pl-grupa-ukryta');}); this.textContent=this.textContent==='>'?'v':'>'" title="Rozwiń/zwiń szczegóły">&gt;</span>
                                @endif
                                <strong>{{ $w['grupa'] }}</strong>
                            </span>
                            @if(isset($w['oper_result_biezacy']))
                            <span class="raport-pl-oper-result-label">— Operational Result (Margin1 - Direct)</span>
                            @endif
                        </td>
                        <td class="text-right">@if(isset($w['oper_result_plan']) && $w['oper_result_plan'] !== null){{ number_format($w['oper_result_plan'], 2, ',', ' ') }}@else—@endif</td>
                        <td class="text-right">@if($pctBiezacy !== null){{ number_format($pctBiezacy, 1, ',', ' ') }}%@else—@endif</td>
                        <td class="text-right">@if(isset($w['oper_result_narastajaco']))<strong>{{ number_format($w['oper_result_narastajaco'], 2, ',', ' ') }}</strong>@else—@endif</td>
                        <td class="text-right">@if(isset($w['oper_result_biezacy']))<strong>{{ number_format($w['oper_result_biezacy'], 2, ',', ' ') }}</strong>@else—@endif</td>
                        <td class="text-right">@if(isset($w['oper_result_poprzednie_okresy']))<strong>{{ number_format($w['oper_result_poprzednie_okresy'], 2, ',', ' ') }}</strong>@else—@endif</td>
                    </tr>
                    @else
                    @include('raport-pl._wiersz', ['w' => $w, 'loop' => $loop, 'miesiac' => $miesiac ?? 12])
                    @endif
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
