@extends('layouts.app')

@section('title', 'Nowa grupa – ' . config('app.name'))

@section('content')
<div class="page-content">
    <div class="page-header">
        <h1 class="page-title">Nowa grupa</h1>
        <a href="{{ route('grupy.index') }}" class="btn btn-outline">← Lista</a>
    </div>

    <form action="{{ route('grupy.store') }}" method="POST" class="form-card">
        @include('grupy._form', ['grupa' => new \App\Models\Grupa()])
    </form>
</div>
@endsection
