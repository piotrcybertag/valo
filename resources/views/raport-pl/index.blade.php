@extends('layouts.app')

@section('title', 'Raport P&L – ' . config('app.name'))

@section('content')
    <div class="page-content">
        <div class="page-header">
            <h1 class="page-title">Raport P&L</h1>
        </div>
        <div class="rounded-lg border border-[#e3e3e0] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] p-6 shadow-sm max-w-lg">
            <p class="text-[#1b1b18] dark:text-[#EDEDEC] mb-4">Wybierz import, z którego zrobić raport:</p>
            @if($imports->isEmpty())
                <p class="text-sm text-gray-500 dark:text-gray-400">Brak importów danych. <a href="{{ route('import.index') }}" class="text-[#1e3a5f] underline">Zaimportuj dane</a> w sekcji Import.</p>
            @else
                <form action="{{ route('raport-pl.index') }}" method="get" class="space-y-4">
                    <div class="form-row">
                        <label for="import_id" class="block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC] mb-1">Import</label>
                        <select name="import_id" id="import_id" required class="form-input">
                            <option value="">— wybierz import —</option>
                            @foreach($imports as $imp)
                                <option value="{{ $imp->id }}">
                                    {{ $imp->okres_nazwa ?? '—' }} (import {{ $imp->created_at->format('Y-m-d H:i') }}, {{ $imp->dane_count }} wierszy)
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Pokaż raport</button>
                </form>
            @endif
        </div>
    </div>
@endsection
