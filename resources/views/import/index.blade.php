@extends('layouts.app')

@section('title', 'Import – ' . config('app.name'))

@section('content')
<div class="page-content page-content--wide">
    <div class="page-header">
        <h1 class="page-title">Historia importów</h1>
        <div class="page-actions">
            <a href="{{ route('import.create') }}" class="btn btn-primary">Dodaj import</a>
        </div>
    </div>

    @if(session('success'))
        <p class="alert alert-success">{{ session('success') }}</p>
    @endif
    @if(session('error'))
        <p class="alert-danger">{{ session('error') }}</p>
    @endif
    @if($errors->any())
        <div class="alert-danger">
            <ul class="list-disc list-inside" style="margin:0;">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if(session('import_exists') && session('import_replacement_pending'))
    @php
        $existing = session('import_exists');
    @endphp
    <div class="alert-warning mb-4">
        <p class="font-medium mb-2">Import za {{ $existing['okres_nazwa'] ?? '—' }} już istnieje.</p>
        <p class="text-sm mb-3">Obecny import: {{ $existing['created_at'] ?? '—' }}, {{ $existing['dane_count'] ?? 0 }} wierszy.</p>
        <p class="text-sm mb-4">Czy chcesz ponownie zaimportować dane za ten okres? Istniejący import zostanie zastąpiony w bazie danych.</p>
        <form action="{{ route('import.dane.zastap') }}" method="post" class="inline">
            @csrf
            <input type="hidden" name="potwierdz" value="1">
            <button type="submit" class="btn btn-primary">Tak, zastąp import</button>
        </form>
        <a href="{{ route('import.dane.zastap-anuluj') }}" class="btn btn-outline" style="margin-left:0.5rem">Anuluj</a>
    </div>
    @endif

    @if(session('missing_konta'))
    <div class="alert-warning mb-4">
        <p class="font-medium mb-2">Następujące konta z pliku importu nie występują w planie kont:</p>
        @php $brakujace = session('missing_konta'); @endphp
        <p class="text-sm mb-3">{{ count($brakujace) > 50 ? implode(', ', array_slice($brakujace, 0, 50)) . '... oraz ' . (count($brakujace) - 50) . ' innych' : implode(', ', $brakujace) }}</p>
        <p class="text-sm mb-4">Czy mimo to zaimportować dane?</p>
        <form action="{{ route('import.dane.potwierdz') }}" method="post" class="inline">
            @csrf
            <input type="hidden" name="potwierdz" value="1">
            <button type="submit" class="btn btn-primary">Tak, zaimportuj mimo to</button>
        </form>
        <a href="{{ route('import.anuluj-pending') }}" class="btn btn-outline" style="margin-left:0.5rem">Anuluj</a>
    </div>
    @endif

    @if(isset($imports) && $imports->isNotEmpty())
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Okres (rok / miesiąc)</th>
                    <th>Data importu</th>
                    <th>Plik</th>
                    <th>Liczba wierszy</th>
                    <th class="col-actions">Akcje</th>
                </tr>
            </thead>
            <tbody>
                @foreach($imports as $imp)
                <tr>
                    <td>{{ $imp->okres_nazwa ?? '—' }}</td>
                    <td>{{ $imp->created_at->format('Y-m-d H:i') }}</td>
                    <td>{{ $imp->nazwa_pliku ?? '—' }}</td>
                    <td>{{ $imp->dane_count }}</td>
                    <td class="col-actions">
                        <a href="{{ route('raport-pl.show', $imp) }}" class="btn-icon" title="Podejrzyj">
                            <svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                        </a>
                        <form action="{{ route('import.destroy', $imp) }}" method="POST" class="inline-form" onsubmit="return confirm('Czy na pewno usunąć ten import? Zostaną usunięte wszystkie powiązane dane.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-icon btn-icon--danger" title="Usuń">
                                <svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                            </button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
    <p class="empty-state">Brak importów. <a href="{{ route('import.create') }}">Dodaj import</a>, aby zaimportować dane z pliku CSV.</p>
    @endif
</div>
@endsection
