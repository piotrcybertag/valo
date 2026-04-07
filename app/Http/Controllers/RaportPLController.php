<?php

namespace App\Http\Controllers;

use App\Models\Import;
use App\Models\PlanRoczny;
use App\Models\Wip;
use Illuminate\Http\Request;

class RaportPLController extends Controller
{
    private const KATEGORIE = [
        'Sales (income)',
        'CoS',
        'Margin1',
        'Direct',
        'Operational Result',
        'Indirect',
        'EBIT',
        'Financial',
        'Income',
    ];

    /** Wiersze wyliczane + Sales – pogrubione (tekst i liczby). */
    private const KATEGORIE_POGRUBIONE = [
        'Sales (income)',
        'Margin1',
        'Operational Result',
        'EBIT',
        'Income',
    ];

    private static function kategoria(string $rodzaj): ?string
    {
        $r = trim($rodzaj);
        if (stripos($r, 'Income') === 0 && stripos($r, 'Financial') !== 0) {
            return 'Sales (income)';
        }
        if ($r === 'Costs of sales') {
            return 'CoS';
        }
        if ($r === 'Direct costs') {
            return 'Direct';
        }
        if ($r === 'Indirect costs' || $r === 'Operational costs') {
            return 'Indirect';
        }
        if ($r === 'Financial income' || $r === 'Financial costs') {
            return 'Financial';
        }
        return null;
    }

    public function index(Request $request)
    {
        $imports = Import::withCount('dane')->orderByDesc('created_at')->get();

        if ($request->filled('import_id')) {
            $import = Import::find($request->input('import_id'));
            if ($import) {
                return redirect()->route('raport-pl.show', $import);
            }
        }

        $latest = $imports->first();
        if ($latest) {
            return redirect()->route('raport-pl.show', $latest);
        }

        return view('raport-pl.index', compact('imports'));
    }

    private const KATEGORIE_PER_GRUPA = ['Sales (income)', 'CoS', 'Margin1', 'Direct'];

    /** Agreguje dane importu i zwraca wartości biezacy per grupa/kategoria. */
    private static function agregujBiezacy($dane, $planKont, array $kolejnoscGrup): array
    {
        $sumy = ['Indirect' => 0.0, 'Financial' => 0.0];
        $directOgolne = 0.0;
        $grupy = [];
        foreach ($kolejnoscGrup as $g) {
            $grupy[$g] = ['Sales (income)' => 0.0, 'CoS' => 0.0, 'Direct' => 0.0];
        }

        foreach ($dane as $row) {
            $pk = $planKont->get($row->nr);
            $grupa = $pk ? ($pk->grupa ?? '') : '';
            $rodzaj = $pk ? ($pk->rodzaj_pozycji ?? '') : '';
            $kat = self::kategoria((string) $rodzaj);
            if ($kat === null) {
                continue;
            }

            $ma4 = (float) ($row->ma4 ?? 0);
            $wn4 = (float) ($row->wn4 ?? 0);
            $ma5 = (float) ($row->ma5 ?? 0);
            $wn5 = (float) ($row->wn5 ?? 0);

            if ($kat === 'Indirect' || $kat === 'Financial') {
                if ($kat === 'Indirect') {
                    $sumy['Indirect'] += $wn4;
                } else {
                    $sumy['Financial'] += $ma4 - $wn4;
                }
                continue;
            }

            if (! isset($grupy[$grupa])) {
                $grupy[$grupa] = ['Sales (income)' => 0.0, 'CoS' => 0.0, 'Direct' => 0.0];
            }

            if ($kat === 'Sales (income)') {
                $grupy[$grupa]['Sales (income)'] += $ma4;
            } elseif ($kat === 'CoS') {
                $grupy[$grupa]['CoS'] += $wn4;
            } else {
                if ($grupa === '') {
                    $directOgolne += $wn4;
                } else {
                    $grupy[$grupa]['Direct'] += $wn4;
                }
            }
        }

        $sumSales = 0.0;
        $sumOperResult = 0.0;
        foreach ($kolejnoscGrup as $g) {
            $sb = $grupy[$g] ?? ['Sales (income)' => 0, 'CoS' => 0, 'Direct' => 0];
            $sumSales += $sb['Sales (income)'];
            $margin1 = $sb['Sales (income)'] - $sb['CoS'];
            $operResult = $margin1 - $sb['Direct'];
            $sumOperResult += $operResult;
        }
        $sumOperResult -= $directOgolne;
        $ebit = $sumOperResult - $sumy['Indirect'];
        $income = $ebit - $sumy['Financial'];

        return [
            'sumSales' => $sumSales,
            'sumOperResult' => $sumOperResult,
            'grupy' => $grupy,
            'directOgolne' => $directOgolne,
            'indirect' => $sumy['Indirect'],
            'financial' => $sumy['Financial'],
            'ebit' => $ebit,
            'income' => $income,
        ];
    }

    /** Zwraca per-konto wartości biezacy z importu: [nr => value]. */
    private static function pozycjePerMiesiac($dane, $planKont): array
    {
        $out = [];
        foreach ($dane as $row) {
            $pk = $planKont->get($row->nr);
            $rodzaj = $pk ? ($pk->rodzaj_pozycji ?? '') : '';
            $kat = self::kategoria((string) $rodzaj);
            if ($kat === null) {
                continue;
            }
            $ma4 = (float) ($row->ma4 ?? 0);
            $wn4 = (float) ($row->wn4 ?? 0);
            $val = match ($kat) {
                'Sales (income)' => $ma4,
                'Indirect' => $wn4,
                'Financial' => $ma4 - $wn4,
                default => $wn4,
            };
            $out[$row->nr] = $val;
        }
        return $out;
    }

    public function show(Import $import)
    {
        $import->load('dane');
        $planKont = \App\Models\PlanKont::all()->keyBy('nr');

        $sumyBiezacy = ['Indirect' => 0.0, 'Financial' => 0.0];
        $sumyNarastajaco = ['Indirect' => 0.0, 'Financial' => 0.0];
        $pozycjeIndirect = [];
        $pozycjeFinancial = [];
        $directOgolneBiezacy = 0.0;
        $directOgolneNarastajaco = 0.0;
        $pozycjeDirectOgolne = [];
        $sumyGrupaBiezacy = [];
        $sumyGrupaNarastajaco = [];
        $pozycjeSales = [];
        $kolejnoscGrup = [];

        foreach ($import->dane as $row) {
            $pk = $planKont->get($row->nr);
            $grupa = $pk ? ($pk->grupa ?? '') : '';
            $rodzaj = $pk ? ($pk->rodzaj_pozycji ?? '') : '';
            $kat = self::kategoria((string) $rodzaj);
            if ($kat === null) {
                continue;
            }

            $ma4 = (float) ($row->ma4 ?? 0);
            $wn4 = (float) ($row->wn4 ?? 0);
            $ma5 = (float) ($row->ma5 ?? 0);
            $wn5 = (float) ($row->wn5 ?? 0);

            if ($kat === 'Indirect' || $kat === 'Financial') {
                if ($kat === 'Indirect') {
                    $sumyBiezacy['Indirect'] += $wn4;
                    $sumyNarastajaco['Indirect'] += $wn5;
                    $pozycjeIndirect[] = [
                        'nr' => $row->nr,
                        'nazwa' => $pk ? ($pk->nazwa ?? '') : '',
                        'biezacy' => $wn4,
                        'narastajaco' => $wn5,
                    ];
                } else {
                    $valB = $ma4 - $wn4;
                    $valN = $ma5 - $wn5;
                    $sumyBiezacy['Financial'] += $valB;
                    $sumyNarastajaco['Financial'] += $valN;
                    $pozycjeFinancial[] = [
                        'nr' => $row->nr,
                        'nazwa' => $pk ? ($pk->nazwa ?? '') : '',
                        'biezacy' => $valB,
                        'narastajaco' => $valN,
                    ];
                }
                continue;
            }

            if (! isset($sumyGrupaBiezacy[$grupa])) {
                $kolejnoscGrup[] = $grupa;
                $sumyGrupaBiezacy[$grupa] = [
                    'Sales (income)' => 0.0, 'CoS' => 0.0, 'Direct' => 0.0,
                ];
                $sumyGrupaNarastajaco[$grupa] = [
                    'Sales (income)' => 0.0, 'CoS' => 0.0, 'Direct' => 0.0,
                ];
                $pozycjeSales[$grupa] = [];
                $pozycjeCoS[$grupa] = [];
                $pozycjeDirect[$grupa] = [];
            }

            if ($kat === 'Sales (income)') {
                $sumyGrupaBiezacy[$grupa]['Sales (income)'] += $ma4;
                $sumyGrupaNarastajaco[$grupa]['Sales (income)'] += $ma5;
                $pozycjeSales[$grupa][] = [
                    'nr' => $row->nr,
                    'nazwa' => $pk ? ($pk->nazwa ?? '') : '',
                    'biezacy' => $ma4,
                    'narastajaco' => $ma5,
                ];
            } elseif ($kat === 'CoS') {
                $sumyGrupaBiezacy[$grupa]['CoS'] += $wn4;
                $sumyGrupaNarastajaco[$grupa]['CoS'] += $wn5;
                $pozycjeCoS[$grupa][] = [
                    'nr' => $row->nr,
                    'nazwa' => $pk ? ($pk->nazwa ?? '') : '',
                    'biezacy' => $wn4,
                    'narastajaco' => $wn5,
                ];
            } else {
                if ($grupa === '') {
                    $directOgolneBiezacy += $wn4;
                    $directOgolneNarastajaco += $wn5;
                    $pozycjeDirectOgolne[] = [
                        'nr' => $row->nr,
                        'nazwa' => $pk ? ($pk->nazwa ?? '') : '',
                        'biezacy' => $wn4,
                        'narastajaco' => $wn5,
                    ];
                } else {
                    $sumyGrupaBiezacy[$grupa]['Direct'] += $wn4;
                    $sumyGrupaNarastajaco[$grupa]['Direct'] += $wn5;
                    $pozycjeDirect[$grupa][] = [
                        'nr' => $row->nr,
                        'nazwa' => $pk ? ($pk->nazwa ?? '') : '',
                        'biezacy' => $wn4,
                        'narastajaco' => $wn5,
                    ];
                }
            }
        }

        $sumOperResultBiezacy = 0.0;
        $sumOperResultNarastajaco = 0.0;
        $sumSalesBiezacy = 0.0;
        $sumSalesNarastajaco = 0.0;
        $sumCoSBiezacy = 0.0;
        $sumCoSNarastajaco = 0.0;
        $sumDirectBiezacy = 0.0;
        $sumDirectNarastajaco = 0.0;
        foreach ($kolejnoscGrup as $g) {
            $sumSalesBiezacy += $sumyGrupaBiezacy[$g]['Sales (income)'] ?? 0;
            $sumSalesNarastajaco += $sumyGrupaNarastajaco[$g]['Sales (income)'] ?? 0;
            $sumCoSBiezacy += $sumyGrupaBiezacy[$g]['CoS'] ?? 0;
            $sumCoSNarastajaco += $sumyGrupaNarastajaco[$g]['CoS'] ?? 0;
            $sumDirectBiezacy += $sumyGrupaBiezacy[$g]['Direct'] ?? 0;
            $sumDirectNarastajaco += $sumyGrupaNarastajaco[$g]['Direct'] ?? 0;
        }

        $rok = $import->data_okresu ? (int) $import->data_okresu->format('Y') : (int) date('Y');
        $miesiac = $import->data_okresu ? (int) $import->data_okresu->format('n') : 12;

        $wipPerMiesiac = [];
        for ($wm = 1; $wm <= $miesiac; $wm++) {
            $wipPerMiesiac[$wm] = (float) Wip::query()
                ->where('rok', $rok)
                ->where('miesiac', $wm)
                ->sum('wartosc');
        }
        $wipNarastajaco = array_sum($wipPerMiesiac);

        $daneMiesieczne = [];
        $impGrudzien = Import::whereYear('data_okresu', $rok - 1)
            ->whereMonth('data_okresu', 12)
            ->with('dane')
            ->first();
        $daneMiesiecznePozycje = [];
        if ($impGrudzien) {
            $daneMiesieczne[0] = self::agregujBiezacy($impGrudzien->dane, $planKont, $kolejnoscGrup);
            $daneMiesiecznePozycje[0] = self::pozycjePerMiesiac($impGrudzien->dane, $planKont);
        }
        for ($m = 1; $m < $miesiac; $m++) {
            $imp = Import::whereYear('data_okresu', $rok)
                ->whereMonth('data_okresu', $m)
                ->with('dane')
                ->first();
            if ($imp) {
                $daneMiesieczne[$m] = self::agregujBiezacy($imp->dane, $planKont, $kolejnoscGrup);
                $daneMiesiecznePozycje[$m] = self::pozycjePerMiesiac($imp->dane, $planKont);
            }
        }
        $daneMiesieczne[$miesiac] = self::agregujBiezacy($import->dane, $planKont, $kolejnoscGrup);
        $daneMiesiecznePozycje[$miesiac] = self::pozycjePerMiesiac($import->dane, $planKont);

        $roznica = static function (array $dane, callable $getVal): array {
            $out = [];
            foreach ($dane as $m => $dm) {
                if ($m === 0) {
                    continue;
                }
                $prev = $dane[$m - 1] ?? null;
                $out[$m] = $getVal($dm) - ($prev ? $getVal($prev) : 0);
            }
            return $out;
        };

        $enrichPozycje = static function (array $pozycje, array $daneMiesiecznePozycje, int $miesiac): array {
            return array_map(function ($p) use ($daneMiesiecznePozycje, $miesiac) {
                $nr = $p['nr'];
                $wm = [];
                for ($m = 1; $m <= $miesiac; $m++) {
                    $curr = $daneMiesiecznePozycje[$m][$nr] ?? 0;
                    $prev = $daneMiesiecznePozycje[$m - 1][$nr] ?? 0;
                    $wm[$m] = $curr - $prev;
                }
                $p['wartosci_miesieczne'] = $wm;
                return $p;
            }, $pozycje);
        };

        foreach ($kolejnoscGrup as $g) {
            if (isset($pozycjeSales[$g])) {
                $pozycjeSales[$g] = $enrichPozycje($pozycjeSales[$g], $daneMiesiecznePozycje, $miesiac);
            }
            if (isset($pozycjeCoS[$g])) {
                $pozycjeCoS[$g] = $enrichPozycje($pozycjeCoS[$g], $daneMiesiecznePozycje, $miesiac);
            }
            if (isset($pozycjeDirect[$g])) {
                $pozycjeDirect[$g] = $enrichPozycje($pozycjeDirect[$g], $daneMiesiecznePozycje, $miesiac);
            }
        }
        $pozycjeDirectOgolne = $enrichPozycje($pozycjeDirectOgolne, $daneMiesiecznePozycje, $miesiac);
        $pozycjeIndirect = $enrichPozycje($pozycjeIndirect, $daneMiesiecznePozycje, $miesiac);
        $pozycjeFinancial = $enrichPozycje($pozycjeFinancial, $daneMiesiecznePozycje, $miesiac);

        $planRoczny = PlanRoczny::first();
        $planByGrupaKod = \App\Models\PlanRocznyGrupa::with('grupa')->get()->keyBy(fn ($p) => $p->grupa?->kod ?? '');
        $sumSalesPlan = $planByGrupaKod->sum(fn ($p) => (float) ($p->sales_plan ?? 0));
        $sumCoSPlan = $planByGrupaKod->sum(fn ($p) => (float) ($p->cos_plan ?? 0));
        $sumDirectPlan = $planByGrupaKod->sum(fn ($p) => (float) ($p->direct_plan ?? 0));
        $sumOperResultPlan = 0.0;
        foreach ($kolejnoscGrup as $gk) {
            $prg = $planByGrupaKod->get($gk);
            if ($prg) {
                $sumOperResultPlan += (float) ($prg->sales_plan ?? 0) - (float) ($prg->cos_plan ?? 0) - (float) ($prg->direct_plan ?? 0);
            }
        }
        $sumOperResultPlan -= (float) ($planRoczny?->direct_ogolne_plan ?? 0);
        $ebitPlan = $planRoczny && $planRoczny->indirect_plan !== null ? $sumOperResultPlan - (float) $planRoczny->indirect_plan : null;
        $incomePlan = $ebitPlan !== null && $planRoczny && $planRoczny->finansowe_ogolne_plan !== null ? $ebitPlan - (float) $planRoczny->finansowe_ogolne_plan : null;

        $wiersze = [];
        $wiersze[] = [
            'typ' => 'podsumowanie_sales',
            'biezacy' => $sumSalesBiezacy,
            'narastajaco' => $sumSalesNarastajaco,
            'plan' => $sumSalesPlan ?: null,
            'wartosci_miesieczne' => $roznica($daneMiesieczne, fn ($dm) => $dm['sumSales'] ?? 0),
        ];

        $grupaIdx = 0;
        foreach ($kolejnoscGrup as $grupa) {
            $sb = $sumyGrupaBiezacy[$grupa];
            $sn = $sumyGrupaNarastajaco[$grupa];
            $margin1B = $sb['Sales (income)'] - $sb['CoS'];
            $margin1N = $sn['Sales (income)'] - $sn['CoS'];
            $operResultB = $margin1B - $sb['Direct'];
            $operResultN = $margin1N - $sn['Direct'];
            $sumOperResultBiezacy += $operResultB;
            $sumOperResultNarastajaco += $operResultN;

            $prg = $planByGrupaKod->get($grupa);
            $operResultPlan = $prg ? ((float) ($prg->sales_plan ?? 0)) - ((float) ($prg->cos_plan ?? 0)) - ((float) ($prg->direct_plan ?? 0)) : null;
            $nazwaGrupy = $grupa === '' ? '—' : $grupa;
            $getOperResult = fn ($dm) => (($dm['grupy'][$grupa]['Sales (income)'] ?? 0) - ($dm['grupy'][$grupa]['CoS'] ?? 0)) - ($dm['grupy'][$grupa]['Direct'] ?? 0);
            $operResultMiesieczne = $roznica($daneMiesieczne, $getOperResult);
            $wiersze[] = [
                'typ' => 'naglowek_grupy',
                'grupa' => $nazwaGrupy,
                'oper_result_biezacy' => $operResultB,
                'oper_result_narastajaco' => $operResultN,
                'oper_result_plan' => $operResultPlan,
                'grupa_idx' => $grupaIdx,
                'wartosci_miesieczne' => $operResultMiesieczne,
            ];
            $grupaIdx++;

            foreach (self::KATEGORIE_PER_GRUPA as $k) {
                if ($k === 'Margin1') {
                    $biezacy = $margin1B;
                    $narastajaco = $margin1N;
                    $planVal = $prg ? ((float) ($prg->sales_plan ?? 0)) - ((float) ($prg->cos_plan ?? 0)) : null;
                } else {
                    $biezacy = $sb[$k] ?? 0;
                    $narastajaco = $sn[$k] ?? 0;
                    $planVal = $prg ? match ($k) {
                        'Sales (income)' => (float) ($prg->sales_plan ?? 0),
                        'CoS' => (float) ($prg->cos_plan ?? 0),
                        'Direct' => (float) ($prg->direct_plan ?? 0),
                        default => null,
                    } : null;
                }
                $pozycje = match ($k) {
                    'Sales (income)' => $pozycjeSales[$grupa] ?? [],
                    'CoS' => $pozycjeCoS[$grupa] ?? [],
                    'Direct' => $pozycjeDirect[$grupa] ?? [],
                    default => null,
                };
                $getVal = $k === 'Margin1'
                    ? fn ($dm) => (($dm['grupy'][$grupa]['Sales (income)'] ?? 0) - ($dm['grupy'][$grupa]['CoS'] ?? 0))
                    : fn ($dm) => $dm['grupy'][$grupa][$k] ?? 0;
                $wartosciM = $roznica($daneMiesieczne, $getVal);
                $wiersze[] = [
                    'typ' => 'wiersz',
                    'kategoria' => $k,
                    'biezacy' => $biezacy,
                    'narastajaco' => $narastajaco,
                    'plan' => $planVal,
                    'pogrubiony' => in_array($k, self::KATEGORIE_POGRUBIONE, true),
                    'pozycje' => $pozycje,
                    'grupa_idx' => $grupaIdx - 1,
                    'wartosci_miesieczne' => $wartosciM,
                ];
            }
        }

        $sumOperResultBiezacy -= $directOgolneBiezacy;
        $sumOperResultNarastajaco -= $directOgolneNarastajaco;

        $mapPlan = [
            'Indirect' => $planRoczny?->indirect_plan,
            'EBIT' => $ebitPlan,
            'Financial' => $planRoczny?->finansowe_ogolne_plan,
            'Income' => $incomePlan,
        ];

        $ebitBiezacy = $sumOperResultBiezacy - $sumyBiezacy['Indirect'];
        $ebitNarastajaco = $sumOperResultNarastajaco - $sumyNarastajaco['Indirect'];
        $incomeBiezacy = $ebitBiezacy - $sumyBiezacy['Financial'];
        $incomeNarastajaco = $ebitNarastajaco - $sumyNarastajaco['Financial'];

        $wiersze[] = [
            'typ' => 'wiersz',
            'kategoria' => 'Koszty ogólne Direct (bez grupy)',
            'biezacy' => $directOgolneBiezacy,
            'narastajaco' => $directOgolneNarastajaco,
            'plan' => $planRoczny?->direct_ogolne_plan,
            'pogrubiony' => false,
            'pozycje' => $pozycjeDirectOgolne,
            'wartosci_miesieczne' => $roznica($daneMiesieczne, fn ($dm) => $dm['directOgolne'] ?? 0),
        ];
        $sumMargin1Biezacy = $sumSalesBiezacy - $sumCoSBiezacy;
        $sumMargin1Narastajaco = $sumSalesNarastajaco - $sumCoSNarastajaco;
        $sumDirectTotalBiezacy = $sumDirectBiezacy + $directOgolneBiezacy;
        $sumDirectTotalNarastajaco = $sumDirectNarastajaco + $directOgolneNarastajaco;
        $sumMargin1Plan = $sumSalesPlan - $sumCoSPlan;
        $sumDirectTotalPlan = $sumDirectPlan + (float) ($planRoczny?->direct_ogolne_plan ?? 0);
        $getSumMargin1 = fn ($dm) => ($dm['sumSales'] ?? 0) - array_sum(array_map(fn ($g) => $dm['grupy'][$g]['CoS'] ?? 0, $kolejnoscGrup));
        $getSumDirect = fn ($dm) => array_sum(array_map(fn ($g) => $dm['grupy'][$g]['Direct'] ?? 0, $kolejnoscGrup)) + ($dm['directOgolne'] ?? 0);
        $wiersze[] = [
            'typ' => 'wiersz',
            'kategoria' => 'Margin1 (suma ze wszystkich grup)',
            'biezacy' => $sumMargin1Biezacy,
            'narastajaco' => $sumMargin1Narastajaco,
            'plan' => $sumMargin1Plan,
            'pogrubiony' => true,
            'pozycje' => null,
            'wartosci_miesieczne' => $roznica($daneMiesieczne, $getSumMargin1),
        ];
        $wiersze[] = [
            'typ' => 'wiersz',
            'kategoria' => 'Direct (suma ze wszystkich grup)',
            'biezacy' => $sumDirectTotalBiezacy,
            'narastajaco' => $sumDirectTotalNarastajaco,
            'plan' => $sumDirectTotalPlan,
            'pogrubiony' => true,
            'pozycje' => null,
            'wartosci_miesieczne' => $roznica($daneMiesieczne, $getSumDirect),
        ];
        $wiersze[] = [
            'typ' => 'podsumowanie_oper_result',
            'biezacy' => $sumOperResultBiezacy,
            'narastajaco' => $sumOperResultNarastajaco,
            'plan' => $sumOperResultPlan ?: null,
            'wartosci_miesieczne' => $roznica($daneMiesieczne, fn ($dm) => $dm['sumOperResult'] ?? 0),
        ];
        $wiersze[] = [
            'typ' => 'wiersz',
            'kategoria' => 'Indirect',
            'biezacy' => $sumyBiezacy['Indirect'],
            'narastajaco' => $sumyNarastajaco['Indirect'],
            'plan' => $mapPlan['Indirect'],
            'pogrubiony' => false,
            'pozycje' => $pozycjeIndirect,
            'wartosci_miesieczne' => $roznica($daneMiesieczne, fn ($dm) => $dm['indirect'] ?? 0),
        ];

        $wiersze[] = [
            'typ' => 'wiersz',
            'kategoria' => 'EBIT',
            'biezacy' => $ebitBiezacy,
            'narastajaco' => $ebitNarastajaco,
            'plan' => $mapPlan['EBIT'],
            'pogrubiony' => true,
            'wartosci_miesieczne' => $roznica($daneMiesieczne, fn ($dm) => $dm['ebit'] ?? 0),
        ];
        $wiersze[] = [
            'typ' => 'wiersz',
            'kategoria' => 'Financial',
            'biezacy' => $sumyBiezacy['Financial'],
            'narastajaco' => $sumyNarastajaco['Financial'],
            'plan' => $mapPlan['Financial'],
            'pogrubiony' => false,
            'pozycje' => $pozycjeFinancial,
            'wartosci_miesieczne' => $roznica($daneMiesieczne, fn ($dm) => $dm['financial'] ?? 0),
        ];
        $wiersze[] = [
            'typ' => 'wiersz',
            'kategoria' => 'Income',
            'biezacy' => $incomeBiezacy,
            'narastajaco' => $incomeNarastajaco,
            'plan' => $mapPlan['Income'],
            'pogrubiony' => true,
            'wartosci_miesieczne' => $roznica($daneMiesieczne, fn ($dm) => $dm['income'] ?? 0),
        ];

        $wartosciMiesieczneIncome = $roznica($daneMiesieczne, fn ($dm) => $dm['income'] ?? 0);
        $wartosciMiesieczneIncomeAdjusted = [];
        for ($mi = 1; $mi <= $miesiac; $mi++) {
            $wartosciMiesieczneIncomeAdjusted[$mi] = ($wartosciMiesieczneIncome[$mi] ?? 0) + ($wipPerMiesiac[$mi] ?? 0);
        }

        $wiersze[] = [
            'typ' => 'wiersz',
            'kategoria' => 'WIP',
            'narastajaco' => $wipNarastajaco,
            'plan' => null,
            'pogrubiony' => false,
            'pozycje' => null,
            'wartosci_miesieczne' => $wipPerMiesiac,
        ];
        $wiersze[] = [
            'typ' => 'wiersz',
            'kategoria' => 'Income Adjusted',
            'narastajaco' => $incomeNarastajaco + $wipNarastajaco,
            'plan' => null,
            'pogrubiony' => true,
            'pozycje' => null,
            'wartosci_miesieczne' => $wartosciMiesieczneIncomeAdjusted,
        ];

        $imports = Import::withCount('dane')->orderByDesc('created_at')->get();

        return view('raport-pl.show', [
            'import' => $import,
            'imports' => $imports,
            'wiersze' => $wiersze,
            'miesiac' => $miesiac,
        ]);
    }
}
