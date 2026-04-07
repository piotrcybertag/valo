@extends('layouts.app')

@section('title', 'WIP – podgląd – ' . config('app.name'))

@section('content')
<div class="page-content">
    <div class="page-header">
        <h1 class="page-title">WIP – podgląd</h1>
        <div class="page-actions">
            <a href="{{ route('wip.edit', $wip) }}" class="btn btn-primary">Edytuj</a>
            <a href="{{ route('wip.index') }}" class="btn btn-outline">← Lista</a>
        </div>
    </div>

    <div class="form-card" style="max-width:32rem;">
        <dl style="margin:0;">
            <dt class="form-row" style="margin-bottom:0.25rem;font-weight:600;">Miesiąc</dt>
            <dd style="margin:0 0 1rem;">{{ $wip->okres_nazwa }}</dd>
            <dt class="form-row" style="margin-bottom:0.25rem;font-weight:600;">Nazwa projektu</dt>
            <dd style="margin:0 0 1rem;">{{ $wip->nazwa_projektu }}</dd>
            <dt class="form-row" style="margin-bottom:0.25rem;font-weight:600;">Wartość</dt>
            <dd style="margin:0;">{{ number_format($wip->wartosc, 2, ',', ' ') }}</dd>
        </dl>
    </div>
</div>
@endsection
