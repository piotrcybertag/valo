@csrf
@if(isset($user) && $user->exists)
    @method('PUT')
@endif

<div class="form-row">
    <label for="email">Email</label>
    <input type="email" name="email" id="email" value="{{ old('email', $user->email ?? '') }}" required class="form-input" autocomplete="email">
    @error('email')<span class="form-error">{{ $message }}</span>@enderror
</div>
<div class="form-row">
    <label for="password">{{ isset($user) && $user->exists ? 'Nowe hasło (zostaw puste, aby nie zmieniać)' : 'Hasło' }}</label>
    <input type="password" name="password" id="password" class="form-input" autocomplete="{{ isset($user) && $user->exists ? 'new-password' : 'new-password' }}">
    @error('password')<span class="form-error">{{ $message }}</span>@enderror
</div>
<div class="form-row">
    <label for="password_confirmation">Potwierdź hasło</label>
    <input type="password" name="password_confirmation" id="password_confirmation" class="form-input" autocomplete="new-password">
</div>
<div class="form-row">
    <label for="typ">Typ</label>
    <input type="text" name="typ" id="typ" value="{{ old('typ', $user->typ ?? '') }}" maxlength="255" class="form-input" placeholder="np. admin, użytkownik">
    @error('typ')<span class="form-error">{{ $message }}</span>@enderror
</div>
<div class="form-actions">
    <button type="submit" class="btn btn-primary">Zapisz</button>
    <a href="{{ route('users.index') }}" class="btn btn-outline">Anuluj</a>
</div>
