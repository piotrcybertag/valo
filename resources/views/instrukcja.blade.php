@extends('layouts.app')

@section('title', 'Instrukcja – ' . config('app.name'))

@section('content')
<div class="page-content page-content--wide">
    <h1 class="page-title" style="margin-bottom: 1.5rem;">Instrukcja działania programu</h1>

    <div class="instrukcja-content">
        <ol class="instrukcja-list">
            <li>
                <strong>Import planu kont z pliku CSV</strong>
                <p>Plan kont można zaimportować z pliku CSV o następującym formacie:</p>
                <ul>
                    <li><strong>Separator:</strong> średnik (;)</li>
                    <li><strong>Kolumny:</strong> Nr;Nazwa;Rodzaj pozycji (rodzaj pozycji jest opcjonalny)</li>
                    <li><strong>Grupa</strong> nie jest importowana — można ją ustawić ręcznie w edycji pozycji planu kont</li>
                    <li><strong>Pierwszy wiersz</strong> może być nagłówkiem (Nr/Nazwa lub Konto/Nazwa, ewentualnie Rodzaj pozycji) — zostanie pominięty</li>
                    <li><strong>Kodowanie:</strong> UTF-8, ISO-8859-2 lub Windows-1250</li>
                </ul>
                <p>Import wykonuje się w <a href="{{ route('import-plan-kont.index') }}">Ustawienia → Import planu kont</a> — wybierz plik CSV i kliknij „Importuj plan kont”.</p>
            </li>
            <li>
                <strong>Przypisywanie rodzaju pozycji</strong>
                <p>Plan kont ma hierarchię: konto nadrzędne (np. 410) i konta podrzędne (np. 410-01, 410-02, 410-01-01). Konto jest podrzędne, gdy jego numer zaczyna się od numeru konta nadrzędnego i myślnika.</p>
                <p><strong>Logika kaskadowa:</strong> Gdy w edycji pozycji planu kont ustawisz rodzaj pozycji dla konta nadrzędnego, program automatycznie przypisze ten sam rodzaj pozycji wszystkim kontom podrzędnym. Np. ustawiając „Direct costs” dla konta 410, otrzymasz ten sam rodzaj dla 410-01, 410-02, 410-01-01 itd.</p>
                <p>Rodzaj pozycji ustawia się w <a href="{{ route('plan-kont.index') }}">Kartoteki → Plan kont</a> — edytuj wybraną pozycję i wybierz rodzaj z listy rozwijanej.</p>
            </li>
        </ol>
    </div>
</div>
@endsection
