<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/teste', function () {
    return view('welcome');
});

Route::get('/bo', function(){
    return view('bo');
});
/* Pannello RTP (basic auth: RTP_PANEL_USER / RTP_PANEL_PASSWORD in .env) */
Route::middleware('rtp.auth')->group(function () {
    Route::get('/rtp', [\App\Http\Controllers\RtpPanelController::class, 'index'])->name('rtp.index');
    Route::get('/rtp/simulatore', [\App\Http\Controllers\RtpPanelController::class, 'simulator'])->name('rtp.simulator');
    Route::post('/rtp/{gameId}', [\App\Http\Controllers\RtpPanelController::class, 'update'])->whereNumber('gameId')->name('rtp.update');

    Route::get('/palinsesto', [\App\Http\Controllers\PalinsestoPanelController::class, 'index'])->name('palinsesto.index');
    Route::post('/palinsesto', [\App\Http\Controllers\PalinsestoPanelController::class, 'save'])->name('palinsesto.save');
    Route::post('/palinsesto/elimina', [\App\Http\Controllers\PalinsestoPanelController::class, 'destroy'])->name('palinsesto.destroy');
    Route::post('/palinsesto/genera', [\App\Http\Controllers\PalinsestoPanelController::class, 'generate'])->name('palinsesto.generate');
    Route::post('/palinsesto/ricalcola', [\App\Http\Controllers\PalinsestoPanelController::class, 'recalc'])->name('palinsesto.recalc');
    Route::post('/palinsesto/sposta', [\App\Http\Controllers\PalinsestoPanelController::class, 'shift'])->name('palinsesto.shift');
});

/* Pannello tecnico di manutenzione, non collegato ai menu (basic auth: TECH_PANEL_USER / TECH_PANEL_PASSWORD in .env) */
Route::middleware('tech.auth')->prefix('tecnico')->group(function () {
    Route::get('/', [\App\Http\Controllers\MaintenancePanelController::class, 'index'])->name('tecnico.index');
    Route::post('/{operation}', [\App\Http\Controllers\MaintenancePanelController::class, 'run'])
        ->where('operation', 'palinsesto|invio-card|esita|annulla|risultati')->name('tecnico.run');
});
