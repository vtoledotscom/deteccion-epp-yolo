<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\EventEvidenceController;
use App\Http\Controllers\EventExportController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/home', function () {
    return redirect()->route('dashboard');
})->name('home');

Route::middleware(['auth', 'active'])->group(function () {

    Route::get('/', function () {
        return redirect()->route('dashboard');
    });

    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware('permission:view_dashboard')
        ->name('dashboard');

    /*
    |--------------------------------------------------------------------------
    | Exportaciones protegidas por permisos
    |--------------------------------------------------------------------------
    */

    Route::get('/events/export/csv', [EventExportController::class, 'csv'])
        ->middleware('permission:export_csv')
        ->name('events.export.csv');

    Route::get('/events/export/pdf', [EventExportController::class, 'pdf'])
        ->middleware('permission:export_pdf')
        ->name('events.export.pdf');

    Route::get('/reports/export/csv', [ReportController::class, 'exportCsv'])
        ->middleware('permission:export_csv')
        ->name('reports.export.csv');

    Route::get('/reports/export/pdf', [ReportController::class, 'exportPdf'])
        ->middleware('permission:export_pdf')
        ->name('reports.export.pdf');
        
    Route::get('/events/{eventId}/pdf', [EventExportController::class, 'eventPdf'])
        ->middleware('permission:export_pdf')
        ->name('events.export.event-pdf');

    /*
    |--------------------------------------------------------------------------
    | Eventos
    |--------------------------------------------------------------------------
    */

    Route::view('/events', 'events.index')
        ->middleware('permission:view_events')
        ->name('events.index');

    Route::view('/events/open', 'events.open')
        ->middleware('permission:view_events')
        ->name('events.open');

    Route::get('/events/{eventId}', [EventController::class, 'show'])
        ->middleware('permission:view_event_detail')
        ->name('events.show');

    /*
    |--------------------------------------------------------------------------
    | Evidencias protegidas por login
    |--------------------------------------------------------------------------
    */

    Route::get('/media/events/{eventId}/annotated', [EventEvidenceController::class, 'annotated'])
        ->middleware('permission:view_event_detail')
        ->name('media.events.annotated');

    Route::get('/media/events/{eventId}/full', [EventEvidenceController::class, 'full'])
        ->middleware('permission:view_event_detail')
        ->name('media.events.full');

    Route::get('/media/events/{eventId}/crop', [EventEvidenceController::class, 'crop'])
        ->middleware('permission:view_event_detail')
        ->name('media.events.crop');

    Route::get('/media/events/{eventId}/video', [EventEvidenceController::class, 'video'])
        ->middleware('permission:view_event_detail')
        ->name('media.events.video');

    /*
    |--------------------------------------------------------------------------
    | Reportes visibles para usuarios autenticados
    |--------------------------------------------------------------------------
    */

    Route::get('/reports', [ReportController::class, 'index'])
        ->middleware('permission:view_reports')
        ->name('reports.index');

    /*
    |--------------------------------------------------------------------------
    | Usuarios
    |--------------------------------------------------------------------------
    */

    Route::resource('users', UserController::class)
        ->except(['show'])
        ->middleware('permission:manage_users');

    Route::patch('/users/{user}/activate', [UserController::class, 'activate'])
        ->middleware('permission:manage_users')
        ->name('users.activate');

    Route::patch('/users/{user}/disable', [UserController::class, 'disable'])
        ->middleware('permission:manage_users')
        ->name('users.disable');
});
