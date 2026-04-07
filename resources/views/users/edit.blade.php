@extends('layouts.app')

@section('title', 'Edycja użytkownika – ' . config('app.name'))

@section('content')
<div class="page-content">
    <div class="page-header">
        <h1 class="page-title">Edycja użytkownika</h1>
        <a href="{{ route('users.index') }}" class="btn btn-outline">← Lista</a>
    </div>

    <form action="{{ route('users.update', $user) }}" method="POST" class="form-card">
        @include('users._form', ['user' => $user])
    </form>
</div>
@endsection
