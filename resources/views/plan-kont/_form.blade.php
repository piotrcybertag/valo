@csrf
@if(isset($planKont) && $planKont->exists)
    @method('PUT')
@endif

<div class="form-row">
    <label for="nr">Nr</label>
    <input type="text" name="nr" id="nr" value="{{ old('nr', $planKont->nr ?? '') }}" maxlength="50" class="form-input">
    @error('nr')<span class="form-error">{{ $message }}</span>@enderror
</div>
<div class="form-row">
    <label for="grupa">Grupa</label>
    <select name="grupa" id="grupa" class="form-input">
        <option value="">— wybierz —</option>
        @foreach($grupy as $g)
            <option value="{{ $g->kod }}" @selected(old('grupa', $planKont->grupa ?? '') === $g->kod)>{{ $g->kod }}@if($g->opis) – {{ $g->opis }}@endif</option>
        @endforeach
    </select>
    @error('grupa')<span class="form-error">{{ $message }}</span>@enderror
</div>
<div class="form-row">
    <label for="nazwa">Nazwa</label>
    <input type="text" name="nazwa" id="nazwa" value="{{ old('nazwa', $planKont->nazwa ?? '') }}" maxlength="255" class="form-input">
    @error('nazwa')<span class="form-error">{{ $message }}</span>@enderror
</div>
<div class="form-row">
    <label for="rodzaj_pozycji">Rodzaj pozycji</label>
    <select name="rodzaj_pozycji" id="rodzaj_pozycji" class="form-input">
        <option value="">— wybierz —</option>
        @foreach($rodzajePozycji as $rodzaj)
            <option value="{{ $rodzaj }}" @selected(old('rodzaj_pozycji', $planKont->rodzaj_pozycji ?? '') === $rodzaj)>{{ $rodzaj }}</option>
        @endforeach
    </select>
    @error('rodzaj_pozycji')<span class="form-error">{{ $message }}</span>@enderror
</div>
<div class="form-actions">
    <button type="submit" class="btn btn-primary">Zapisz</button>
    <a href="{{ route('plan-kont.index') }}" class="btn btn-outline">Anuluj</a>
</div>
