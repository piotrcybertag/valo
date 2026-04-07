@extends('layouts.app')

@section('title', 'Nowa pozycja planu kont – ' . config('app.name'))

@section('content')
<div class="page-content">
    <div class="page-header">
        <h1 class="page-title">Nowa pozycja planu kont</h1>
        <a href="{{ route('plan-kont.index') }}" class="btn btn-outline">← Lista</a>
    </div>

    <form action="{{ route('plan-kont.store') }}" method="POST" class="form-card">
        @include('plan-kont._form', ['planKont' => new \App\Models\PlanKont()])
    </form>
</div>
@endsection
