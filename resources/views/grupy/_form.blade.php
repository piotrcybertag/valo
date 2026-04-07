@csrf
@if(isset($grupa) && $grupa->exists)
    @method('PUT')
@endif

<div class="form-row">
    <label for="kod">Kod</label>
    <input type="text" name="kod" id="kod" value="{{ old('kod', $grupa->kod ?? '') }}" maxlength="255" class="form-input">
    @error('kod')<span class="form-error">{{ $message }}</span>@enderror
</div>
<div class="form-row">
    <label for="opis">Opis</label>
    <input type="text" name="opis" id="opis" value="{{ old('opis', $grupa->opis ?? '') }}" maxlength="255" class="form-input">
    @error('opis')<span class="form-error">{{ $message }}</span>@enderror
</div>
<div class="form-actions">
    <button type="submit" class="btn btn-primary">Zapisz</button>
    <a href="{{ route('grupy.index') }}" class="btn btn-outline">Anuluj</a>
</div>
