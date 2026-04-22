<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\EventEvidenceController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EventExportController;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

Route::view('/events', 'events.index')->name('events.index');
Route::view('/events/open', 'events.open')->name('events.open');
Route::get('/events/{eventId}', [EventController::class, 'show'])->name('events.show');

Route::get('/media/events/{eventId}/annotated', [EventEvidenceController::class, 'annotated'])->name('media.events.annotated');
Route::get('/media/events/{eventId}/full', [EventEvidenceController::class, 'full'])->name('media.events.full');
Route::get('/media/events/{eventId}/crop', [EventEvidenceController::class, 'crop'])->name('media.events.crop');
Route::get('/media/events/{eventId}/video', [EventEvidenceController::class, 'video'])->name('media.events.video');

Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
Route::get('/reports/export/csv', [ReportController::class, 'exportCsv'])->name('reports.export.csv');
Route::get('/reports/export/pdf', [ReportController::class, 'exportPdf'])->name('reports.export.pdf');
Route::get('/events/export/csv', [EventExportController::class, 'csv'])->name('events.export.csv');
Route::get('/events/export/pdf', [EventExportController::class, 'pdf'])->name('events.export.pdf');

use App\Models\EppEvent;

Route::get('/test-events', function () {
    return EppEvent::with('evidence')
        ->orderByDesc('id')
        ->take(5)
        ->get();
});