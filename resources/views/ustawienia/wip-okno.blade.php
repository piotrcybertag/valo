@extends('layouts.app')

@section('title', 'Okno WIP – ' . config('app.name'))

@section('content')
<div class="page-content page-content--wide w-full max-w-3xl mx-auto py-6">
    <div class="page-header mb-6">
        <h1 class="page-title">Okno WIP</h1>
        <a href="{{ route('plan-roczny.edit') }}" class="btn btn-outline shrink-0">← Ustawienia</a>
    </div>

    @if (session('success'))
        <div class="alert-success mb-4">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert-danger mb-4">{{ session('error') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert-danger mb-4">
            <ul class="list-disc list-inside m-0">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <p class="text-sm text-[#6b7280] dark:text-[#a1a1aa] mb-6">Ustal okres WIP, termin końcowy oraz adresy project managerów. Lista e-maili jest zapisywana w bazie — po zapisaniu pozostaje na kolejne wejścia. Po „Wyślij WIP request” każdy adres otrzyma wiadomość z linkiem do formularza (bez logowania).</p>

    <form action="{{ route('wip-okno.send') }}" method="post" class="form-card max-w-none" id="form-wip-okno">
        @csrf
        <div class="form-row">
            <label for="wip_rok">Rok (okres WIP)</label>
            <select name="rok" id="wip_rok" class="form-input" required>
                @foreach (range(date('Y'), date('Y') - 5) as $y)
                    <option value="{{ $y }}" @selected(old('rok', date('Y')) == $y)>{{ $y }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-row">
            <label for="wip_miesiac">Miesiąc WIP</label>
            <select name="miesiac" id="wip_miesiac" class="form-input" required>
                @foreach (['1' => 'Styczeń', '2' => 'Luty', '3' => 'Marzec', '4' => 'Kwiecień', '5' => 'Maj', '6' => 'Czerwiec', '7' => 'Lipiec', '8' => 'Sierpień', '9' => 'Wrzesień', '10' => 'Październik', '11' => 'Listopad', '12' => 'Grudzień'] as $m => $nazwa)
                    <option value="{{ $m }}" @selected((int) old('miesiac', (int) date('n')) === (int) $m)>{{ $nazwa }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-row">
            <label for="data_koncowa">Data końcowa (do tego dnia włącznie)</label>
            <input type="date" name="data_koncowa" id="data_koncowa" class="form-input" value="{{ old('data_koncowa') }}" required min="{{ date('Y-m-d') }}" />
        </div>

        <p class="text-sm font-medium mb-2 mt-4">Adresy e-mail project managerów</p>
        <table class="data-table mb-4" id="wip-emails-table">
            <thead>
                <tr>
                    <th>E-mail</th>
                    <th class="col-actions" style="width:4rem;">Usuń</th>
                </tr>
            </thead>
            <tbody id="wip-emails-body">
                @php $oldEmails = old('emails', $storedEmails ?? ['']); @endphp
                @foreach ($oldEmails as $idx => $em)
                <tr class="wip-email-row">
                    <td>
                        <input type="email" name="emails[]" class="form-input" value="{{ $em }}" placeholder="pm@firma.pl" {{ $loop->first ? 'required' : '' }} />
                    </td>
                    <td class="text-center">
                        @if (!$loop->first)
                            <button type="button" class="btn btn-outline btn-sm wip-remove-row" tabindex="-1">×</button>
                        @else
                            <span class="text-gray-400">—</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <p class="mb-6">
            <button type="button" class="btn btn-outline btn-sm" id="wip-add-email">Dodaj wiersz</button>
        </p>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Wyślij WIP request</button>
        </div>
    </form>
</div>
<script>
(function () {
    var tbody = document.getElementById('wip-emails-body');
    var addBtn = document.getElementById('wip-add-email');
    if (!tbody || !addBtn) return;
    addBtn.addEventListener('click', function () {
        var tr = document.createElement('tr');
        tr.className = 'wip-email-row';
        tr.innerHTML = '<td><input type="email" name="emails[]" class="form-input" placeholder="pm@firma.pl" /></td>' +
            '<td class="text-center"><button type="button" class="btn btn-outline btn-sm wip-remove-row" tabindex="-1">×</button></td>';
        tbody.appendChild(tr);
    });
    tbody.addEventListener('click', function (e) {
        if (e.target.classList.contains('wip-remove-row')) {
            var row = e.target.closest('tr');
            if (row && tbody.querySelectorAll('tr').length > 1) row.remove();
        }
    });
})();
</script>
@endsection
