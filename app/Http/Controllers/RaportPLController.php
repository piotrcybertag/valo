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

    /** Plan, narastająco i wszystkie kolumny miesięczne ≈ 0 (null plan lub 0 liczy się jako „puste”). */
    private static function raportPlKolumnyWszystkieZera(array $w): bool
    {
        $eps = 1e-6;
        $z = static fn ($x): bool => $x === null || abs((float) $x) < $eps;
        $typ = $w['typ'] ?? '';

        if ($typ === 'naglowek_grupy') {
            $plan = $w['oper_result_plan'] ?? null;
            $nar = (float) ($w['oper_result_narastajaco'] ?? 0);
            $biez = (float) ($w['oper_result_biezacy'] ?? 0);
        } elseif ($typ === 'podsumowanie_sales' || $typ === 'podsumowanie_oper_result') {
            $plan = $w['plan'] ?? null;
            $nar = (float) ($w['narastajaco'] ?? 0);
            $biez = (float) ($w['biezacy'] ?? 0);
        } else {
            $plan = $w['plan'] ?? null;
            $nar = (float) ($w['narastajaco'] ?? 0);
            $biez = (float) ($w['biezacy'] ?? 0);
        }

        return $z($plan) && $z($nar) && $z($biez);
    }

    /**
     * Ukrywa wiersze z samymi zerami. Dla bloku grupy: usuwa nagłówek tylko gdy nagłówek i wszystkie wiersze szczegółów są zerowe.
     */
    private static function raportPlFiltrujWierszeZerowe(array $wiersze): array
    {
        $out = [];
        $i = 0;
        $n = count($wiersze);
        while ($i < $n) {
            $w = $wiersze[$i];
            $typ = $w['typ'] ?? '';

            if ($typ === 'naglowek_grupy') {
                $gidx = $w['grupa_idx'] ?? -1;
                $j = $i + 1;
                while ($j < $n
                    && ($wiersze[$j]['typ'] ?? '') === 'wiersz'
                    && ($wiersze[$j]['grupa_idx'] ?? -999) === $gidx) {
                    $j++;
                }

                $headZero = self::raportPlKolumnyWszystkieZera($w);
                $keptChildren = [];
                for ($k = $i + 1; $k < $j; $k++) {
                    if (! self::raportPlKolumnyWszystkieZera($wiersze[$k])) {
                        $keptChildren[] = $wiersze[$k];
                    }
                }

                if ($headZero && $keptChildren === []) {
                    // cała grupa zerowa — brak wierszy
                } else {
                    $out[] = $wiersze[$i];
                    foreach ($keptChildren as $row) {
                        $out[] = $row;
                    }
                }
                $i = $j;

                continue;
            }

            if (! self::raportPlKolumnyWszystkieZera($w)) {
                $out[] = $w;
            }
            $i++;
        }

        return $out;
    }

    /** Uzupełnia pozycjom szczegółowym kolumnę „poprzednie okresy” = narastająco − bieżący. */
    private static function raportPlPozycjeZPoprzednimi(array $pozycje): array
    {
        return array_map(static function (array $p): array {
            $p['poprzednie_okresy'] = (float) ($p['narastajaco'] ?? 0) - (float) ($p['biezacy'] ?? 0);

            return $p;
        }, $pozycje);
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
        $pozycjeCoS = [];
        $pozycjeDirect = [];
        $kolejnoscGrup = [];

        foreach ($import->dane as $row) {
            $pk = $planKont->get($row->nr);
            $grupa = $pk ? ($pk->grupa ?? '') : '';
            $rodzaj = $pk ? ($pk->rodzaj_pozycji ?? '') : '';
            $kat = self::kategoria((string) $rodzaj);
            if ($kat === null) {
                continue;
            }

            $ma2 = (float) ($row->ma2 ?? 0);
            $wn2 = (float) ($row->wn2 ?? 0);
            $ma3 = (float) ($row->ma3 ?? 0);
            $wn3 = (float) ($row->wn3 ?? 0);

            if ($kat === 'Indirect' || $kat === 'Financial') {
                if ($kat === 'Indirect') {
                    $sumyBiezacy['Indirect'] += $wn2;
                    $sumyNarastajaco['Indirect'] += $wn3;
                    $pozycjeIndirect[] = [
                        'nr' => $row->nr,
                        'nazwa' => $pk ? ($pk->nazwa ?? '') : '',
                        'biezacy' => $wn2,
                        'narastajaco' => $wn3,
                    ];
                } else {
                    $valB = $ma2 - $wn2;
                    $valN = $ma3 - $wn3;
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
                $sumyGrupaBiezacy[$grupa]['Sales (income)'] += $ma2;
                $sumyGrupaNarastajaco[$grupa]['Sales (income)'] += $ma3;
                $pozycjeSales[$grupa][] = [
                    'nr' => $row->nr,
                    'nazwa' => $pk ? ($pk->nazwa ?? '') : '',
                    'biezacy' => $ma2,
                    'narastajaco' => $ma3,
                ];
            } elseif ($kat === 'CoS') {
                $sumyGrupaBiezacy[$grupa]['CoS'] += $wn2;
                $sumyGrupaNarastajaco[$grupa]['CoS'] += $wn3;
                $pozycjeCoS[$grupa][] = [
                    'nr' => $row->nr,
                    'nazwa' => $pk ? ($pk->nazwa ?? '') : '',
                    'biezacy' => $wn2,
                    'narastajaco' => $wn3,
                ];
            } else {
                if ($grupa === '') {
                    $directOgolneBiezacy += $wn2;
                    $directOgolneNarastajaco += $wn3;
                    $pozycjeDirectOgolne[] = [
                        'nr' => $row->nr,
                        'nazwa' => $pk ? ($pk->nazwa ?? '') : '',
                        'biezacy' => $wn2,
                        'narastajaco' => $wn3,
                    ];
                } else {
                    $sumyGrupaBiezacy[$grupa]['Direct'] += $wn2;
                    $sumyGrupaNarastajaco[$grupa]['Direct'] += $wn3;
                    $pozycjeDirect[$grupa][] = [
                        'nr' => $row->nr,
                        'nazwa' => $pk ? ($pk->nazwa ?? '') : '',
                        'biezacy' => $wn2,
                        'narastajaco' => $wn3,
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

        // WIP tylko za miesiąc okresu importu (nie suma YTD z poprzednich miesięcy).
        $wipMiesiacaRaportu = (float) Wip::query()
            ->where('rok', $rok)
            ->where('miesiac', $miesiac)
            ->sum('wartosc');

        foreach ($kolejnoscGrup as $g) {
            if (isset($pozycjeSales[$g])) {
                $pozycjeSales[$g] = self::raportPlPozycjeZPoprzednimi($pozycjeSales[$g]);
            }
            if (isset($pozycjeCoS[$g])) {
                $pozycjeCoS[$g] = self::raportPlPozycjeZPoprzednimi($pozycjeCoS[$g]);
            }
            if (isset($pozycjeDirect[$g])) {
                $pozycjeDirect[$g] = self::raportPlPozycjeZPoprzednimi($pozycjeDirect[$g]);
            }
        }
        $pozycjeDirectOgolne = self::raportPlPozycjeZPoprzednimi($pozycjeDirectOgolne);
        $pozycjeIndirect = self::raportPlPozycjeZPoprzednimi($pozycjeIndirect);
        $pozycjeFinancial = self::raportPlPozycjeZPoprzednimi($pozycjeFinancial);

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
            'poprzednie_okresy' => $sumSalesNarastajaco - $sumSalesBiezacy,
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
            $wiersze[] = [
                'typ' => 'naglowek_grupy',
                'grupa' => $nazwaGrupy,
                'oper_result_biezacy' => $operResultB,
                'oper_result_narastajaco' => $operResultN,
                'oper_result_plan' => $operResultPlan,
                'oper_result_poprzednie_okresy' => $operResultN - $operResultB,
                'grupa_idx' => $grupaIdx,
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
                $wiersze[] = [
                    'typ' => 'wiersz',
                    'kategoria' => $k,
                    'biezacy' => $biezacy,
                    'narastajaco' => $narastajaco,
                    'plan' => $planVal,
                    'poprzednie_okresy' => $narastajaco - $biezacy,
                    'pogrubiony' => in_array($k, self::KATEGORIE_POGRUBIONE, true),
                    'pozycje' => $pozycje,
                    'grupa_idx' => $grupaIdx - 1,
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
            'poprzednie_okresy' => $directOgolneNarastajaco - $directOgolneBiezacy,
            'pogrubiony' => false,
            'pozycje' => $pozycjeDirectOgolne,
        ];
        $sumMargin1Biezacy = $sumSalesBiezacy - $sumCoSBiezacy;
        $sumMargin1Narastajaco = $sumSalesNarastajaco - $sumCoSNarastajaco;
        $sumDirectTotalBiezacy = $sumDirectBiezacy + $directOgolneBiezacy;
        $sumDirectTotalNarastajaco = $sumDirectNarastajaco + $directOgolneNarastajaco;
        $sumMargin1Plan = $sumSalesPlan - $sumCoSPlan;
        $sumDirectTotalPlan = $sumDirectPlan + (float) ($planRoczny?->direct_ogolne_plan ?? 0);
        $wiersze[] = [
            'typ' => 'wiersz',
            'kategoria' => 'Margin1 (suma ze wszystkich grup)',
            'biezacy' => $sumMargin1Biezacy,
            'narastajaco' => $sumMargin1Narastajaco,
            'plan' => $sumMargin1Plan,
            'poprzednie_okresy' => $sumMargin1Narastajaco - $sumMargin1Biezacy,
            'pogrubiony' => true,
            'pozycje' => null,
        ];
        $wiersze[] = [
            'typ' => 'wiersz',
            'kategoria' => 'Direct (suma ze wszystkich grup)',
            'biezacy' => $sumDirectTotalBiezacy,
            'narastajaco' => $sumDirectTotalNarastajaco,
            'plan' => $sumDirectTotalPlan,
            'poprzednie_okresy' => $sumDirectTotalNarastajaco - $sumDirectTotalBiezacy,
            'pogrubiony' => true,
            'pozycje' => null,
        ];
        $wiersze[] = [
            'typ' => 'podsumowanie_oper_result',
            'biezacy' => $sumOperResultBiezacy,
            'narastajaco' => $sumOperResultNarastajaco,
            'plan' => $sumOperResultPlan ?: null,
            'poprzednie_okresy' => $sumOperResultNarastajaco - $sumOperResultBiezacy,
        ];
        $wiersze[] = [
            'typ' => 'wiersz',
            'kategoria' => 'Indirect',
            'biezacy' => $sumyBiezacy['Indirect'],
            'narastajaco' => $sumyNarastajaco['Indirect'],
            'plan' => $mapPlan['Indirect'],
            'poprzednie_okresy' => $sumyNarastajaco['Indirect'] - $sumyBiezacy['Indirect'],
            'pogrubiony' => false,
            'pozycje' => $pozycjeIndirect,
        ];

        $wiersze[] = [
            'typ' => 'wiersz',
            'kategoria' => 'EBIT',
            'biezacy' => $ebitBiezacy,
            'narastajaco' => $ebitNarastajaco,
            'plan' => $mapPlan['EBIT'],
            'poprzednie_okresy' => $ebitNarastajaco - $ebitBiezacy,
            'pogrubiony' => true,
        ];
        $wiersze[] = [
            'typ' => 'wiersz',
            'kategoria' => 'Financial',
            'biezacy' => $sumyBiezacy['Financial'],
            'narastajaco' => $sumyNarastajaco['Financial'],
            'plan' => $mapPlan['Financial'],
            'poprzednie_okresy' => $sumyNarastajaco['Financial'] - $sumyBiezacy['Financial'],
            'pogrubiony' => false,
            'pozycje' => $pozycjeFinancial,
        ];
        $wiersze[] = [
            'typ' => 'wiersz',
            'kategoria' => 'Income',
            'biezacy' => $incomeBiezacy,
            'narastajaco' => $incomeNarastajaco,
            'plan' => $mapPlan['Income'],
            'poprzednie_okresy' => $incomeNarastajaco - $incomeBiezacy,
            'pogrubiony' => true,
        ];

        // Niezadekretowane z importu — jak WIP: ta sama kwota w „bieżący” i „narastająco” (brak rozłożenia na wcześniejsze miesiące w jednym imporcie).
        // Income Adjusted YTD nadal bez odejmowania ND w YTD (jak dotychczas), żeby nie dublować korekty.
        $niezadekretowane = (float) ($import->niezadekretowane ?? 0);

        $incomeAdjustedBiezacy = $incomeBiezacy + $wipMiesiacaRaportu - $niezadekretowane;
        $incomeAdjustedNarastajaco = $incomeNarastajaco + $wipMiesiacaRaportu;

        $wiersze[] = [
            'typ' => 'wiersz',
            'kategoria' => 'WIP',
            'biezacy' => $wipMiesiacaRaportu,
            'narastajaco' => $wipMiesiacaRaportu,
            'plan' => null,
            'poprzednie_okresy' => 0.0,
            'pogrubiony' => false,
            'pozycje' => null,
        ];
        $wiersze[] = [
            'typ' => 'wiersz',
            'kategoria' => 'Niezadekretowane',
            'biezacy' => $niezadekretowane,
            'narastajaco' => $niezadekretowane,
            'plan' => null,
            'poprzednie_okresy' => 0.0,
            'pogrubiony' => false,
            'pozycje' => null,
        ];
        $wiersze[] = [
            'typ' => 'wiersz',
            'kategoria' => 'Income Adjusted',
            'biezacy' => $incomeAdjustedBiezacy,
            'narastajaco' => $incomeAdjustedNarastajaco,
            'plan' => null,
            'poprzednie_okresy' => $incomeAdjustedNarastajaco - $incomeAdjustedBiezacy,
            'pogrubiony' => true,
            'pozycje' => null,
        ];

        $wiersze = self::raportPlFiltrujWierszeZerowe($wiersze);

        $imports = Import::withCount('dane')->orderByDesc('created_at')->get();

        return view('raport-pl.show', [
            'import' => $import,
            'imports' => $imports,
            'wiersze' => $wiersze,
            'miesiac' => $miesiac,
        ]);
    }
}
