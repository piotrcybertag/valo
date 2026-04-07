@extends('layouts.app')

@section('title', 'Plan roczny – ' . config('app.name'))

@section('content')
<div class="page-content">
    <div class="page-header">
        <h1 class="page-title">Plan roczny</h1>
        <a href="{{ route('raport-pl.index') }}" class="btn btn-outline">← Raport P&L</a>
    </div>

    @if (session('success'))
        <p class="alert alert-success mb-4">{{ session('success') }}</p>
    @endif

    <form action="{{ route('plan-roczny.update') }}" method="POST" class="form-card">
        @csrf
        @method('PUT')
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Wpisz wartości planu rocznego. Dla każdej grupy: Sales, CoS, Direct. Dodatkowo: Direct ogólne, Indirect, Finansowe ogólne.</p>

        @if($grupy->isEmpty())
            <p class="empty-state mb-4">Brak grup w kartotece. <a href="{{ route('grupy.create') }}">Dodaj grupę</a>, aby móc wpisać plan.</p>
        @else
            <h2 class="text-lg font-semibold mb-3">Plan per grupa</h2>
            @foreach($grupy as $g)
            <div class="plan-grupa-block mb-4 p-4 rounded" style="background:#f9fafb; border:1px solid #e5e7eb;">
                <h3 class="font-medium mb-3">{{ $g->kod ?: '—' }} {{ $g->opis ? '(' . $g->opis . ')' : '' }}</h3>
                <div class="form-row">
                    <label for="grupa_{{ $g->id }}_sales">Sales</label>
                    <input type="number" name="grupa_{{ $g->id }}_sales" id="grupa_{{ $g->id }}_sales" value="{{ old("grupa_{$g->id}_sales", $g->planRocznyGrupa?->sales_plan ?? '') }}" step="0.01" class="form-input">
                </div>
                <div class="form-row">
                    <label for="grupa_{{ $g->id }}_cos">CoS</label>
                    <input type="number" name="grupa_{{ $g->id }}_cos" id="grupa_{{ $g->id }}_cos" value="{{ old("grupa_{$g->id}_cos", $g->planRocznyGrupa?->cos_plan ?? '') }}" step="0.01" class="form-input">
                </div>
                <div class="form-row">
                    <label for="grupa_{{ $g->id }}_direct">Direct</label>
                    <input type="number" name="grupa_{{ $g->id }}_direct" id="grupa_{{ $g->id }}_direct" value="{{ old("grupa_{$g->id}_direct", $g->planRocznyGrupa?->direct_plan ?? '') }}" step="0.01" class="form-input">
                </div>
            </div>
            @endforeach
        @endif

        <h2 class="text-lg font-semibold mb-3 mt-6">Ogólne</h2>
        <div class="form-row">
            <label for="direct_ogolne_plan">Direct ogólne</label>
            <input type="number" name="direct_ogolne_plan" id="direct_ogolne_plan" value="{{ old('direct_ogolne_plan', $plan->direct_ogolne_plan ?? '') }}" step="0.01" class="form-input">
        </div>
        <div class="form-row">
            <label for="indirect_plan">Indirect</label>
            <input type="number" name="indirect_plan" id="indirect_plan" value="{{ old('indirect_plan', $plan->indirect_plan ?? '') }}" step="0.01" class="form-input">
        </div>
        <div class="form-row">
            <label for="finansowe_ogolne_plan">Finansowe ogólne</label>
            <input type="number" name="finansowe_ogolne_plan" id="finansowe_ogolne_plan" value="{{ old('finansowe_ogolne_plan', $plan->finansowe_ogolne_plan ?? '') }}" step="0.01" class="form-input">
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Zapisz</button>
        </div>
    </form>
</div>
@endsection
