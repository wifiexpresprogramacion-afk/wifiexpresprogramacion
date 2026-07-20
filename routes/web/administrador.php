<?php

use Illuminate\Support\Facades\Route;
use App\Http\Livewire\Mikrotik\Administrador\UsersVisitsAdmin;
use App\Http\Livewire\Mikrotik\Administrador\PermanenciaAdmin;
use App\Http\Livewire\Mikrotik\Administrador\PromocionesOfertasAdmin;
use App\Http\Livewire\Mikrotik\Administrador\ConcursosAdmin;
use App\Http\Livewire\Mikrotik\Administrador\MonitoreoAdmin;

Route::middleware(['auth', 'role:aliadoSmartData,admin'])->group(function () {
    Route::get('/smartdata/users-visits-admin/{userId?}', UsersVisitsAdmin::class)->name('smartdata.users-visits.admin');
    Route::get('/smartdata/permanencia-admin', PermanenciaAdmin::class)->name('smartdata.permanencia.admin');
    Route::get('/smartdata/promociones-admin', PromocionesOfertasAdmin::class)->name('smartdata.promociones.admin');
    Route::get('/smartdata/concursos-admin', ConcursosAdmin::class)->name('smartdata.concursos.admin');
    Route::get('/smartdata/monitoreo-admin', MonitoreoAdmin::class)->name('smartdata.monitoreo.admin');
});
