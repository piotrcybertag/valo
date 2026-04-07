@extends('layouts.app')

@section('title', 'valo – Strona główna')

@section('content')
    <div class="home-hero">
        <div class="home-logo">
            @if(file_exists(public_path('logo.png')))
                <img src="{{ asset('logo.png') }}" alt="valo" class="home-logo-img" />
            @elseif(file_exists(public_path('logo.svg')))
                <img src="{{ asset('logo.svg') }}" alt="valo" class="home-logo-img" />
            @else
                <span class="home-logo-text">valo</span>
            @endif
        </div>
        <div class="home-slogan">
            <p>Raport P&L, import danych i plan kont — wszystko w jednym miejscu.</p>
        </div>
    </div>
@endsection
