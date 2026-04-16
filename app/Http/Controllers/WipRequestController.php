<?php

namespace App\Http\Controllers;

use App\Models\Wip;
use App\Models\WipRequestToken;
use Illuminate\Http\Request;

class WipRequestController extends Controller
{
    public function show(Request $request)
    {
        $token = (string) $request->query('t', '');
        if ($token === '') {
            return response()
                ->view('wiprequest.error', ['message' => 'Brak tokenu w linku. Użyj linku z wiadomości e-mail.'], 403);
        }

        $record = WipRequestToken::query()
            ->where('token', $token)
            ->where('expires_at', '>', now())
            ->first();

        if ($record === null) {
            return response()
                ->view('wiprequest.error', ['message' => 'Link wygasł. Poproś o nową wiadomość z aplikacji.'], 403);
        }

        $rok = (int) $record->rok;
        $miesiac = (int) $record->miesiac;
        $email = (string) $record->email;

        $miesiace = ['', 'Styczeń', 'Luty', 'Marzec', 'Kwiecień', 'Maj', 'Czerwiec', 'Lipiec', 'Sierpień', 'Wrzesień', 'Październik', 'Listopad', 'Grudzień'];
        $okresNazwa = ($miesiace[$miesiac] ?? (string) $miesiac) . ' ' . $rok;

        return view('wiprequest.form', [
            'token' => $token,
            'okresNazwa' => $okresNazwa,
            'email' => $email,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            't' => 'required|string|size:64',
            'nr_projektu' => 'required|string|max:255',
            'wartosc' => 'required|numeric',
        ], [
            't.required' => 'Brak tokenu formularza.',
            'nr_projektu.required' => 'Podaj numer projektu.',
            'wartosc.required' => 'Podaj wartość WIP.',
        ]);

        $record = WipRequestToken::query()
            ->where('token', $request->input('t'))
            ->where('expires_at', '>', now())
            ->first();

        if ($record === null) {
            return redirect()->back()->withInput()->withErrors(['t' => 'Formularz wygasł. Otwórz link z maila ponownie.']);
        }

        Wip::create([
            'rok' => (int) $record->rok,
            'miesiac' => (int) $record->miesiac,
            'nazwa_projektu' => $request->input('nr_projektu'),
            'wartosc' => $request->input('wartosc'),
        ]);

        $miesiace = ['', 'Styczeń', 'Luty', 'Marzec', 'Kwiecień', 'Maj', 'Czerwiec', 'Lipiec', 'Sierpień', 'Wrzesień', 'Październik', 'Listopad', 'Grudzień'];
        $m = (int) $record->miesiac;
        $okresNazwa = ($miesiace[$m] ?? (string) $m) . ' ' . (int) $record->rok;

        return view('wiprequest.success', [
            'okresNazwa' => $okresNazwa,
            'token' => $request->input('t'),
            'nr_projektu' => $request->input('nr_projektu'),
        ]);
    }
}
