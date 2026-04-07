@extends('layouts.app')

@section('title', 'Nowy użytkownik – ' . config('app.name'))

@section('content')
<div class="page-content">
    <div class="page-header">
        <h1 class="page-title">Nowy użytkownik</h1>
        <a href="{{ route('users.index') }}" class="btn btn-outline">← Lista</a>
    </div>

    <form action="{{ route('users.store') }}" method="POST" class="form-card">
        @include('users._form', ['user' => new \App\Models\User()])
    </form>
</div>
@endsection
