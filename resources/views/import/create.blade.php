@extends('layouts.app')

@section('title', 'Dodaj import – ' . config('app.name'))

@section('content')
    <div class="rounded-lg border border-[#e3e3e0] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] p-8 shadow-sm">
        @if (session('success'))
            <div class="mb-4 p-4 rounded-lg" style="background:#d1fae5;color:#065f46;border:1px solid #10b981;">
                {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div class="mb-4 p-4 rounded-lg" style="background:#fee2e2;color:#991b1b;border:1px solid #dc2626;">
                {{ session('error') }}
            </div>
        @endif
        @if ($errors->any())
            <div class="mb-4 p-4 rounded-lg" style="background:#fee2e2;color:#991b1b;border:1px solid #dc2626;">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <h2 class="text-xl font-semibold text-[#1b1b18] dark:text-[#EDEDEC] mb-4">Import danych finansowych</h2>
        <p class="text-[#1b1b18] dark:text-[#EDEDEC] mb-6">Wybierz okres, a następnie plik źródłowy CSV. Plik zostanie przerobiony (unikalne nazwy kolumn WN, MA) i zaimportowany.</p>

        <form action="{{ route('import.dane') }}" method="post" enctype="multipart/form-data" class="space-y-4 mb-10">
            @csrf
            <div style="display:flex;flex-wrap:wrap;gap:1.5rem;align-items:flex-end">
                <div class="form-row" style="margin-bottom:0">
                    <label for="rok" class="block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC] mb-1">Rok</label>
                    <select name="rok" id="rok" required class="form-input w-auto min-w-[6rem]">
                        @foreach(range(date('Y'), date('Y') - 10) as $y)
                            <option value="{{ $y }}" @selected(old('rok', date('Y')) == $y)>{{ $y }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-row" style="margin-bottom:0">
                    <label for="miesiac" class="block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC] mb-1">Miesiąc</label>
                    <select name="miesiac" id="miesiac" required class="form-input w-auto min-w-[10rem]">
                        @foreach(['01' => 'Styczeń', '02' => 'Luty', '03' => 'Marzec', '04' => 'Kwiecień', '05' => 'Maj', '06' => 'Czerwiec', '07' => 'Lipiec', '08' => 'Sierpień', '09' => 'Wrzesień', '10' => 'Październik', '11' => 'Listopad', '12' => 'Grudzień'] as $m => $nazwa)
                            <option value="{{ $m }}" @selected(old('miesiac', date('m')) == $m)>{{ $nazwa }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div>
                <label for="plik_csv" class="block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC] mb-1">Plik źródłowy CSV</label>
                <input type="file" name="plik_csv" id="plik_csv" accept=".csv,.txt" required
                    class="block w-full text-sm text-[#1b1b18] dark:text-[#EDEDEC] file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:bg-[#1e3a5f] file:text-white file:cursor-pointer">
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Plik zostanie przerobiony (unikalne nazwy kolumn WN, MA → WN1, MA1, WN2, MA2 itd.) i zaimportowany. Wymagane kolumny: Nr oraz WN/MA (min. wn4, ma4, wn5, ma5 po przeróbce).</p>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="btn btn-primary">Importuj dane finansowe</button>
                <a href="{{ route('import.index') }}" class="btn btn-outline">Anuluj</a>
            </div>
        </form>

        <hr class="my-8 border-[#e3e3e0] dark:border-[#3E3E3A]">

        <h2 class="text-xl font-semibold text-[#1b1b18] dark:text-[#EDEDEC] mb-4">Import danych o projektach</h2>
        <p class="text-[#1b1b18] dark:text-[#EDEDEC] mb-6">Wybierz plik CSV (Nr;Nazwa;WN;MA;WN;MA;...). Kolumny WN i MA zostaną przenumerowane niepowtarzalnie (WN1, MA1, WN2, MA2 itd.) i zaimportowane do bazy projektów.</p>

        <form action="{{ route('piatki.store') }}" method="post" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <div>
                <label for="plik_projekt_csv" class="block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC] mb-1">Plik CSV z danymi projektowymi</label>
                <input type="file" name="plik_csv" id="plik_projekt_csv" accept=".csv,.txt" required
                    class="block w-full text-sm text-[#1b1b18] dark:text-[#EDEDEC] file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:bg-[#1e3a5f] file:text-white file:cursor-pointer">
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Format: Nr;Nazwa;WN;MA;WN;MA;... (separator średnik).</p>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="btn btn-primary">Importuj dane projektowe</button>
            </div>
        </form>
    </div>
@endsection
