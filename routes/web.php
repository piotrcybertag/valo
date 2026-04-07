<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\GrupaController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\ImportProjektController;
use App\Http\Controllers\PlanKontController;
use App\Http\Controllers\PlanRocznyController;
use App\Http\Controllers\RaportPLController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WipController;
use Illuminate\Support\Facades\Route;

Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login')->middleware('guest');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/', function () {
        return view('welcome');
    })->name('welcome');

    Route::get('/instrukcja', fn () => view('instrukcja'))->name('instrukcja');

    Route::get('/import', [ImportController::class, 'index'])->name('import.index');
    Route::get('/import/dodaj', [ImportController::class, 'create'])->name('import.create');
    Route::get('/import/anuluj-pending', [ImportController::class, 'anulujPendingImport'])->name('import.anuluj-pending');
    Route::get('/ustawienia/import-plan-kont', [ImportController::class, 'importPlanKontForm'])->name('import-plan-kont.index');
    Route::post('/ustawienia/import-plan-kont', [ImportController::class, 'importPlanKont'])->name('import-plan-kont.store');
    Route::post('/import/dane', [ImportController::class, 'importDanych'])->name('import.dane');
    Route::post('/import/dane/potwierdz', [ImportController::class, 'importDanychPotwierdz'])->name('import.dane.potwierdz');
    Route::post('/import/dane/zastap', [ImportController::class, 'importDanychZastap'])->name('import.dane.zastap');
    Route::get('/import/dane/zastap-anuluj', [ImportController::class, 'anulujImportZastap'])->name('import.dane.zastap-anuluj');
    Route::delete('/import/{import}', [ImportController::class, 'destroy'])->name('import.destroy');

    Route::post('kartoteki/plan-kont/destroy-all', [PlanKontController::class, 'destroyAll'])
        ->name('plan-kont.destroy-all');
    Route::post('kartoteki/plan-kont/przyjmij-grupy', [PlanKontController::class, 'przyjmijGrupy'])
        ->name('plan-kont.przyjmij-grupy');
    Route::get('kartoteki/plan-kont/pobierz-csv', [PlanKontController::class, 'pobierzCsv'])
        ->name('plan-kont.pobierz-csv');
    Route::resource('kartoteki/plan-kont', PlanKontController::class)->names('plan-kont');
    Route::resource('kartoteki/grupy', GrupaController::class)->names('grupy');
    Route::resource('ustawienia/users', UserController::class)->names('users')->middleware('admin');

    Route::get('/raport-pl', [RaportPLController::class, 'index'])->name('raport-pl.index');
    Route::get('/raport-pl/{import}', [RaportPLController::class, 'show'])->name('raport-pl.show');

    Route::get('/piatki', [ImportProjektController::class, 'index'])->name('piatki.index');
    Route::get('/piatki/{importProjekt}', [ImportProjektController::class, 'show'])->name('piatki.show');
    Route::post('/piatki', [ImportProjektController::class, 'store'])->name('piatki.store');
    Route::delete('/piatki/{importProjekt}', [ImportProjektController::class, 'destroy'])->name('piatki.destroy');

    Route::resource('wip', WipController::class);

    Route::get('/ustawienia/plan-roczny', [PlanRocznyController::class, 'edit'])->name('plan-roczny.edit');
    Route::put('/ustawienia/plan-roczny', [PlanRocznyController::class, 'update'])->name('plan-roczny.update');
});
