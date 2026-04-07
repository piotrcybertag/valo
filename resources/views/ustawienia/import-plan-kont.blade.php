@extends('layouts.app')

@section('title', 'Import planu kont – ' . config('app.name'))

@section('content')
<div class="page-content">
    <div class="page-header">
        <h1 class="page-title">Import planu kont</h1>
        <a href="{{ route('plan-kont.index') }}" class="btn btn-outline">← Plan kont</a>
    </div>

    <div class="form-card">
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

        <form action="{{ route('import-plan-kont.store') }}" method="post" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <div class="form-row">
                <label for="plik_csv" class="block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC] mb-1">Plik CSV z planem kont</label>
                <input type="file" name="plik_csv" id="plik_csv" accept=".csv,.txt" required
                    class="block w-full text-sm text-[#1b1b18] dark:text-[#EDEDEC] file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:bg-[#1e3a5f] file:text-white file:cursor-pointer">
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Oczekiwana struktura: Nr;Nazwa;Rodzaj pozycji lub Konto;Nazwa (separator średnik). Rodzaj pozycji jest opcjonalny. Grupa nie jest importowana. Polskie znaki (Windows-1250) są obsługiwane. Pierwszy wiersz może być nagłówkiem.</p>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Importuj plan kont</button>
                <a href="{{ route('plan-kont.index') }}" class="btn btn-outline">Anuluj</a>
            </div>
        </form>
    </div>
</div>
@endsection
