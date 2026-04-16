<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body style="font-family: system-ui, -apple-system, Segoe UI, sans-serif; line-height: 1.5; color: #1b1b18;">
    <p>Dzień dobry,</p>
    <p>prosimy o uzupełnienie wartości WIP za okres: <strong>{{ $okresNazwa }}</strong>.</p>
    <p>Termin: do {{ $dataKoncowaFormat }}.</p>
    <p><a href="{{ $link }}" style="color: #1e3a5f;">Otwórz formularz WIP</a></p>
    <p style="font-size: 0.875rem; color: #6b7280;">Jeśli przycisk nie działa, skopiuj ten adres do przeglądarki:<br>{{ $link }}</p>
    <p>Pozdrawiamy,<br>{{ config('app.name') }}</p>
</body>
</html>
