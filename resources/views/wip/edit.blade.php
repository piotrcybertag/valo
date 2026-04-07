@extends('layouts.app')

@section('title', 'Edycja WIP – ' . config('app.name'))

@section('content')
<div class="page-content">
    <div class="page-header">
        <h1 class="page-title">Edycja wpisu WIP</h1>
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

    <form action="{{ route('wip.update', $wip) }}" method="POST" class="form-card">
        @method('PUT')
        @include('wip._form', ['wip' => $wip])
    </form>
</div>
@endsection
