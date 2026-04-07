@extends('layouts.app')

@section('title', 'Nowy wpis WIP – ' . config('app.name'))

@section('content')
<div class="page-content">
    <div class="page-header">
        <h1 class="page-title">Nowy wpis WIP</h1>
        <a href="{{ route('wip.index') }}" class="btn btn-outline">← Lista</a>
    </div>

    @if ($errors->any())
        <div class="alert-danger">
            <ul class="list-disc list-inside" style="margin:0;">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('wip.store') }}" method="POST" class="form-card">
        @include('wip._form', ['wip' => new \App\Models\Wip()])
    </form>
</div>
@endsection
