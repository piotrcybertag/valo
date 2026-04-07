@extends('layouts.app')

@section('title', 'Logowanie – ' . config('app.name'))

@section('content')
<div class="page-content login-page">
    <div class="login-layout">
        <div class="login-form-col">
            <div class="page-header">
                <h1 class="page-title">Logowanie</h1>
            </div>

            <form action="{{ route('login') }}" method="POST" class="form-card">
                @csrf
                <div class="form-row">
                    <label for="email">Email</label>
                    <input type="email" name="email" id="email" value="{{ old('email') }}" required autofocus autocomplete="email" class="form-input">
                    @error('email')<span class="form-error">{{ $message }}</span>@enderror
                </div>
                <div class="form-row">
                    <label for="password">Hasło</label>
                    <input type="password" name="password" id="password" required autocomplete="current-password" class="form-input">
                    @error('password')<span class="form-error">{{ $message }}</span>@enderror
                </div>
                <div class="form-row">
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="remember" class="rounded border-gray-300">
                        <span class="text-sm">Zapamiętaj mnie</span>
                    </label>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Zaloguj</button>
                </div>
            </form>
        </div>
        <div class="login-logo-col">
            @if(file_exists(public_path('logo.png')))
                <img src="{{ asset('logo.png') }}" alt="valo" class="login-logo" />
            @elseif(file_exists(public_path('logo.svg')))
                <img src="{{ asset('logo.svg') }}" alt="valo" class="login-logo" />
            @else
                <span class="login-logo-fallback">valo</span>
            @endif
        </div>
    </div>
</div>
@endsection
