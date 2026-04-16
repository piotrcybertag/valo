@extends('layouts.app')

@section('title', 'Dodaj import – ' . config('app.name'))

@section('content')
<div class="page-content page-content--wide w-full max-w-4xl mx-auto py-6">
    <div class="page-header mb-8">
        <div>
            <h1 class="page-title text-2xl sm:text-3xl font-semibold tracking-tight text-[#1b1b18] dark:text-[#EDEDEC]">Import danych</h1>
        </div>
        <a href="{{ route('import.index') }}" class="btn btn-outline shrink-0">← Historia importów</a>
    </div>

    @if (session('success'))
        <div class="alert-success mb-6">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert-danger mb-6">{{ session('error') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert-danger mb-6">
            <ul class="list-disc list-inside m-0">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="import-sections-stack">
    {{-- Import finansowy: jedna zewnętrzna ramka (.import-fin-shell), bez linii / insetów w środku --}}
    <section aria-labelledby="import-finansowe-heading">
        <div class="import-fin-shell">
        <form action="{{ route('import.dane') }}" method="post" enctype="multipart/form-data" id="form-import-finansowe" class="import-fin-form m-0 border-0 bg-transparent p-0 shadow-none">
            @csrf

            <h2 id="import-finansowe-heading" class="import-fin-section-title">Importowanie danych finansowych</h2>
            <p class="import-fin-lead text-sm text-[#6b7280] dark:text-[#a1a1aa] m-0 max-w-2xl">Wgraj pliki CSV — najpierw zobaczysz podgląd (dane finansowe), potem zatwierdzisz zapis do bazy.</p>

            <div class="import-fin-grid">
                <div class="import-fin-col import-fin-col--left">
                    <p class="import-fin-heading">Bieżący okres:</p>
                    <div class="import-fin-period">
                        <select name="rok" id="rok" required class="form-input import-fin-select import-fin-select--rok">
                            @foreach(range(date('Y'), date('Y') - 10) as $y)
                                <option value="{{ $y }}" @selected(old('rok', date('Y')) == $y)>{{ $y }}</option>
                            @endforeach
                        </select>
                        <span class="import-fin-comma" aria-hidden="true">,</span>
                        <select name="miesiac" id="miesiac" required class="form-input import-fin-select import-fin-select--miesiac">
                            @foreach(['01' => 'Styczeń', '02' => 'Luty', '03' => 'Marzec', '04' => 'Kwiecień', '05' => 'Maj', '06' => 'Czerwiec', '07' => 'Lipiec', '08' => 'Sierpień', '09' => 'Wrzesień', '10' => 'Październik', '11' => 'Listopad', '12' => 'Grudzień'] as $m => $nazwa)
                                <option value="{{ $m }}" @selected(old('miesiac', date('m')) == $m)>{{ $nazwa }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="import-fin-col import-fin-col--right">
                    <div class="import-fin-file-block">
                        <p class="import-fin-heading">Wybierz plik zestawienia SiO</p>
                        <input
                            type="file"
                            name="plik_csv"
                            id="plik_csv"
                            accept=".csv,.txt"
                            required
                            class="import-file-input import-fin-file-input"
                        />
                    </div>
                    <div class="import-fin-file-block import-fin-file-block--second">
                        <p class="import-fin-heading">Wybierz plik niezadekretowanych</p>
                        <input
                            type="file"
                            name="plik_niezadekretowane"
                            id="plik_niezadekretowane"
                            accept=".csv,.txt"
                            required
                            class="import-file-input import-fin-file-input"
                        />
                    </div>
                </div>
            </div>

            <div class="import-fin-actions import-fin-actions--shell">
                <button type="submit" id="btn-import-finansowe" class="btn btn-primary" disabled>Importuj dane finansowe</button>
            </div>
        </form>
        </div>
    </section>

    {{-- Import projektów: ta sama logika co .import-fin-shell --}}
    <section aria-labelledby="import-projekty-heading">
        <div class="import-proj-shell">
        <form action="{{ route('piatki.store') }}" method="post" enctype="multipart/form-data" id="form-import-projekty" class="import-fin-form m-0 border-0 bg-transparent p-0 shadow-none">
            @csrf

            <h2 id="import-projekty-heading" class="import-fin-section-title">Importowanie danych projektowych (Piątki)</h2>
            <p class="import-fin-lead text-sm text-[#57534e] dark:text-[#a8a29e] m-0 max-w-2xl">Format <code class="rounded bg-stone-200/80 px-1 py-0.5 text-xs dark:bg-white/10">Nr;Nazwa;WN;MA;…</code> — kolumny WN/MA zostaną przenumerowane. Pierwszy wiersz: nagłówki, separator średnik.</p>

            <div>
                <p class="import-fin-heading">Wybierz plik</p>
                <input
                    type="file"
                    name="plik_csv"
                    id="plik_projekt_csv"
                    accept=".csv,.txt"
                    required
                    class="import-file-input import-proj-file-input"
                />
            </div>

            <div class="import-fin-actions import-fin-actions--shell">
                <button type="submit" id="btn-import-projekty" class="btn btn-primary" disabled>Importuj dane projektowe</button>
            </div>
        </form>
        </div>
    </section>
    </div>
</div>

<script>
(function () {
    function setupImportFinansowe() {
        var sio = document.getElementById('plik_csv');
        var nz = document.getElementById('plik_niezadekretowane');
        var btn = document.getElementById('btn-import-finansowe');
        if (!sio || !nz || !btn) return;
        function sync() {
            var ok = sio.files && sio.files.length > 0 && nz.files && nz.files.length > 0;
            btn.disabled = !ok;
        }
        sio.addEventListener('change', sync);
        nz.addEventListener('change', sync);
        sync();
    }
    function setupImport(inputId, btnId) {
        var input = document.getElementById(inputId);
        var btn = document.getElementById(btnId);
        if (!input || !btn) return;
        function sync() {
            btn.disabled = !(input.files && input.files.length > 0);
        }
        input.addEventListener('change', sync);
        sync();
    }
    setupImportFinansowe();
    setupImport('plik_projekt_csv', 'btn-import-projekty');
})();
</script>
<style>
/* Odstęp między ramkami — gap w flex, bez zapadania marginów */
.import-sections-stack {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
    margin-bottom: 2.5rem;
}
@media (min-width: 640px) {
    .import-sections-stack {
        gap: 1.5rem;
    }
}
/* Sekcja finansowa: jedna ramka, duży padding, zaokrąglenie — bez dodatkowych obwódek w środku */
.import-fin-shell {
    box-sizing: border-box;
    border: 2px solid #94a3b8;
    border-radius: 1.25rem;
    padding: 2rem 1.75rem 2.25rem;
    background: linear-gradient(165deg, #ffffff 0%, #f8fafc 42%, #eef2f7 100%);
    box-shadow: 0 6px 28px rgba(15, 23, 42, 0.07);
}
.dark .import-fin-shell {
    border-color: #64748b;
    background: linear-gradient(165deg, #1c1c1c 0%, #181818 48%, #121212 100%);
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.38);
}
@media (min-width: 640px) {
    .import-fin-shell,
    .import-proj-shell {
        border-radius: 1.5rem;
        padding: 2.75rem 2.75rem 3rem;
    }
}
/* Piątki: ta sama geometria co finanse, ciepła obwódka i tło */
.import-proj-shell {
    box-sizing: border-box;
    border: 2px solid #a8a29e;
    border-radius: 1.25rem;
    padding: 2rem 1.75rem 2.25rem;
    background: linear-gradient(165deg, #fafaf9 0%, #ffffff 40%, #fff7ed 100%);
    box-shadow: 0 6px 28px rgba(120, 113, 108, 0.09);
}
.dark .import-proj-shell {
    border-color: #78716c;
    background: linear-gradient(165deg, #1c1b1a 0%, #1a1918 48%, #181614 100%);
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.38);
}
.import-fin-form {
    display: block;
}
.import-fin-section-title {
    font-size: 1.125rem;
    font-weight: 600;
    letter-spacing: -0.02em;
    color: #1b1b18;
    margin: 0 0 0.75rem;
    line-height: 1.35;
}
.dark .import-fin-section-title {
    color: #ededec;
}
.import-fin-lead {
    margin-top: 0;
    margin-bottom: 2rem;
}
@media (min-width: 640px) {
    .import-fin-lead {
        margin-bottom: 2.5rem;
    }
}
.import-fin-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 2rem 2.5rem;
    align-items: start;
}
@media (min-width: 768px) {
    .import-fin-grid {
        grid-template-columns: 1fr 1fr;
    }
}
.import-fin-file-block--second {
    margin-top: 1.25rem;
}
.import-fin-heading {
    font-size: 0.875rem;
    font-weight: 500;
    color: #4b5563;
    margin: 0 0 0.75rem;
}
.dark .import-fin-heading {
    color: #a1a1aa;
}
.import-fin-period {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.35rem 0.5rem;
}
.import-fin-comma {
    color: #9ca3af;
    font-size: 1rem;
    user-select: none;
}
.import-fin-select--rok.form-input {
    width: 5.25rem !important;
    max-width: 5.25rem !important;
}
.import-fin-select--miesiac.form-input {
    width: 11rem !important;
    max-width: 11rem !important;
}
.import-fin-actions {
    display: flex;
    justify-content: center;
    margin-top: 1.75rem;
    padding-top: 1.25rem;
    border-top: 1px solid rgba(148, 163, 184, 0.2);
}
/* Sekcje w jednej ramce: bez wewnętrznej linii nad przyciskiem */
.import-fin-actions--shell {
    margin-top: 2.5rem;
    padding-top: 0;
    border-top: none;
}
@media (min-width: 640px) {
    .import-fin-actions--shell {
        margin-top: 3rem;
    }
}
#btn-import-finansowe:disabled,
#btn-import-projekty:disabled {
    opacity: 0.45;
    cursor: not-allowed;
}
.sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
}
.import-fin-file-input::file-selector-button,
.import-proj-file-input::file-selector-button,
.import-file-input::file-selector-button {
    margin-right: 0;
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    font-weight: 500;
    border-radius: 0.25rem;
    border: 1px solid transparent;
    background: #1e3a5f;
    color: #fff;
    cursor: pointer;
    font-family: inherit;
}
.import-proj-file-input::file-selector-button {
    margin-right: 0.75rem;
}
.import-fin-file-input:hover::file-selector-button,
.import-proj-file-input:hover::file-selector-button,
.import-file-input:hover::file-selector-button {
    background: #16304d;
}
</style>
@endsection
