@extends('layouts.app')

@section('title', 'Edycja grupy – ' . config('app.name'))

@section('content')
<div class="page-content">
    <div class="page-header">
        <h1 class="page-title">Edycja grupy</h1>
        <a href="{{ route('grupy.index') }}" class="btn btn-outline">← Lista</a>
    </div>

    <form action="{{ route('grupy.update', $grupa) }}" method="POST" class="form-card">
        @include('grupy._form', ['grupa' => $grupa])
    </form>
</div>
@endsection
