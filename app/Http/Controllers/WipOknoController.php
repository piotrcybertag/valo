<?php

namespace App\Http\Controllers;

use App\Mail\WipRequestMail;
use App\Models\WipPmEmail;
use App\Models\WipRequestToken;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class WipOknoController extends Controller
{
    public function edit()
    {
        $rows = WipPmEmail::query()->orderBy('email')->pluck('email')->all();
        if ($rows === []) {
            $rows = [''];
        }

        return view('ustawienia.wip-okno', ['storedEmails' => $rows]);
    }

    public function send(Request $request)
    {
        $validated = $request->validate([
            'rok' => 'required|integer|min:2000|max:2100',
            'miesiac' => 'required|integer|min:1|max:12',
            'data_koncowa' => 'required|date|after_or_equal:today',
            'emails' => 'required|array|min:1',
            'emails.*' => 'nullable|email',
        ], [
            'rok.required' => 'Wybierz rok.',
            'miesiac.required' => 'Wybierz miesiąc WIP.',
            'data_koncowa.required' => 'Podaj datę końcową.',
            'data_koncowa.after_or_equal' => 'Data końcowa nie może być z przeszłości.',
            'emails.required' => 'Podaj co najmniej jeden adres e-mail.',
            'emails.*.email' => 'Nieprawidłowy format adresu e-mail.',
        ]);

        $lista = array_values(array_unique(array_filter(
            array_map('trim', $validated['emails']),
            fn ($e) => $e !== ''
        )));
        if ($lista === []) {
            return redirect()->route('wip-okno.edit')
                ->withInput()
                ->with('error', 'Podaj co najmniej jeden adres e-mail.');
        }

        $expires = Carbon::parse($validated['data_koncowa'])->endOfDay();
        $miesiace = ['', 'Styczeń', 'Luty', 'Marzec', 'Kwiecień', 'Maj', 'Czerwiec', 'Lipiec', 'Sierpień', 'Wrzesień', 'Październik', 'Listopad', 'Grudzień'];
        $okresNazwa = ($miesiace[(int) $validated['miesiac']] ?? '') . ' ' . $validated['rok'];
        $dataKoncowaFormat = $expires->format('d.m.Y');

        DB::transaction(function () use ($lista) {
            WipPmEmail::query()->delete();
            foreach ($lista as $email) {
                WipPmEmail::create(['email' => $email]);
            }
        });

        $sent = 0;
        foreach ($lista as $email) {
            $token = Str::random(64);
            WipRequestToken::create([
                'token' => $token,
                'email' => $email,
                'rok' => $validated['rok'],
                'miesiac' => $validated['miesiac'],
                'expires_at' => $expires,
            ]);
            $url = url('/wiprequest.php?t='.urlencode($token));
            Mail::to($email)->send(new WipRequestMail($url, $okresNazwa, $dataKoncowaFormat));
            $sent++;
        }

        if ($sent === 0) {
            return redirect()->route('wip-okno.edit')
                ->withInput()
                ->with('error', 'Nie wysłano żadnej wiadomości — uzupełnij adresy e-mail.');
        }

        return redirect()->route('wip-okno.edit')
            ->with('success', "Wysłano {$sent} wiadomości z prośbą o uzupełnienie WIP.");
    }
}
