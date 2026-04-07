@csrf
<div class="form-row">
    <label for="rok">Rok</label>
    <select name="rok" id="rok" class="form-input" required>
        @foreach(range(date('Y'), date('Y') - 10) as $y)
            <option value="{{ $y }}" @selected(old('rok', $wip->rok ?? date('Y')) == $y)>{{ $y }}</option>
        @endforeach
    </select>
</div>
<div class="form-row">
    <label for="miesiac">Miesiąc</label>
    <select name="miesiac" id="miesiac" class="form-input" required>
        @foreach(['1' => 'Styczeń', '2' => 'Luty', '3' => 'Marzec', '4' => 'Kwiecień', '5' => 'Maj', '6' => 'Czerwiec', '7' => 'Lipiec', '8' => 'Sierpień', '9' => 'Wrzesień', '10' => 'Październik', '11' => 'Listopad', '12' => 'Grudzień'] as $m => $nazwa)
            <option value="{{ $m }}" @selected((int) old('miesiac', $wip->miesiac ?? (int) date('n')) === (int) $m)>{{ $nazwa }}</option>
        @endforeach
    </select>
</div>
<div class="form-row">
    <label for="nazwa_projektu">Nazwa projektu</label>
    <input type="text" name="nazwa_projektu" id="nazwa_projektu" class="form-input" value="{{ old('nazwa_projektu', $wip->nazwa_projektu ?? '') }}" required maxlength="255" />
</div>
<div class="form-row">
    <label for="wartosc">Wartość</label>
    <input type="number" name="wartosc" id="wartosc" class="form-input" value="{{ old('wartosc', isset($wip->wartosc) ? $wip->wartosc : '') }}" required step="0.01" />
</div>
<div class="form-actions">
    <button type="submit" class="btn btn-primary">Zapisz</button>
    <a href="{{ route('wip.index') }}" class="btn btn-outline">Anuluj</a>
</div>
