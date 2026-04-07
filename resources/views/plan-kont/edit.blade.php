@extends('layouts.app')

@section('title', 'Edycja planu kont – ' . config('app.name'))

@section('content')
<div class="page-content">
    <div class="page-header">
        <h1 class="page-title">Edycja pozycji planu kont</h1>
        <a href="{{ route('plan-kont.index') }}" class="btn btn-outline">← Lista</a>
    </div>

    <form action="{{ route('plan-kont.update', $planKont) }}" method="POST" class="form-card">
        @include('plan-kont._form', ['planKont' => $planKont])
    </form>
</div>
@endsection
