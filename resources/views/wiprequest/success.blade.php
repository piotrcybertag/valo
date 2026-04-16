<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>WIP zapisany — {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
    <style>
        body { font-family: 'Instrument Sans', system-ui, sans-serif; margin: 0; padding: 2rem; background: #fdfdfc; color: #1b1b18; max-width: 28rem; margin-left: auto; margin-right: auto; }
        h1 { font-size: 1.25rem; font-weight: 600; margin: 0 0 0.75rem; color: #065f46; }
        .muted { font-size: 0.875rem; color: #6b7280; margin-bottom: 1.25rem; }
        .actions { display: flex; flex-wrap: wrap; gap: 0.75rem; margin-top: 1.25rem; }
        .btn { display: inline-block; padding: 0.5rem 1rem; font-size: 0.875rem; font-weight: 500; border-radius: 0.25rem; border: 1px solid transparent; cursor: pointer; text-decoration: none; font-family: inherit; }
        .btn-primary { background: #1e3a5f; color: #fff; border-color: #1e3a5f; }
        .btn-primary:hover { background: #16304d; }
        .btn-secondary { background: #fff; color: #374151; border-color: #d1d5db; }
        .btn-secondary:hover { background: #f9fafb; }
    </style>
</head>
<body>
    <h1>Zapisano WIP</h1>
    <p class="muted">Okres: <strong>{{ $okresNazwa }}</strong>. Projekt: <strong>{{ $nr_projektu }}</strong>.</p>
    <p>Czy chcesz dodać kolejny wpis WIP dla tego samego okresu?</p>
    <div class="actions">
        <a href="{{ route('wiprequest.show', ['t' => $token]) }}" class="btn btn-primary">Tak, dodaj kolejny</a>
        <a href="{{ route('wiprequest.thanks') }}" class="btn btn-secondary">Nie, dziękuję</a>
    </div>
</body>
</html>
