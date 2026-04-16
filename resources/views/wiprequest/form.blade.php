<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>WIP — {{ $okresNazwa }} — {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
    <style>
        body { font-family: 'Instrument Sans', system-ui, sans-serif; margin: 0; padding: 2rem; background: #fdfdfc; color: #1b1b18; max-width: 28rem; margin-left: auto; margin-right: auto; }
        h1 { font-size: 1.25rem; font-weight: 600; margin: 0 0 0.5rem; }
        .muted { font-size: 0.875rem; color: #6b7280; margin-bottom: 1.5rem; }
        label { display: block; font-size: 0.875rem; font-weight: 500; margin-bottom: 0.25rem; }
        .form-input { width: 100%; height: 2.25rem; padding: 0.5rem 0.75rem; border: 1px solid #d1d5db; border-radius: 0.25rem; font-size: 0.875rem; box-sizing: border-box; margin-bottom: 1rem; }
        .btn { display: inline-block; padding: 0.5rem 1rem; font-size: 0.875rem; font-weight: 500; border-radius: 0.25rem; border: 1px solid transparent; cursor: pointer; background: #1e3a5f; color: #fff; font-family: inherit; }
        .btn:hover { background: #16304d; }
        .err { color: #b91c1c; font-size: 0.8125rem; margin-bottom: 1rem; }
    </style>
</head>
<body>
    <h1>WIP — {{ $okresNazwa }}</h1>
    <p class="muted">Link przypisany do: {{ $email }}</p>

    <form action="{{ route('wiprequest.store') }}" method="post">
        @csrf
        <input type="hidden" name="t" value="{{ $token }}" />

        <label for="nr_projektu">Nr projektu</label>
        <input type="text" name="nr_projektu" id="nr_projektu" class="form-input" value="{{ old('nr_projektu') }}" required maxlength="255" autocomplete="off" />

        <label for="wartosc">Wartość</label>
        <input type="number" name="wartosc" id="wartosc" class="form-input" value="{{ old('wartosc') }}" required step="0.01" />

        @if ($errors->any())
            <div class="err">{{ $errors->first() }}</div>
        @endif

        <button type="submit" class="btn">Zapisz WIP</button>
    </form>
</body>
</html>
