<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\EventEvidenceController;
use App\Http\Controllers\EventExportController;
use App\Http\Controllers\EventReviewController;
use App\Http\Controllers\OpenEventController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ActivityLogController;
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
        ->middleware(['permission:view_dashboard', 'activity.log:view_dashboard,dashboard,Vista_de_dashboard'])
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
        ->middleware(['permission:view_events', 'activity.log:view_events,events,Vista_de_listado_de_eventos'])
        ->name('events.index');

    Route::get('/events/open', [OpenEventController::class, 'index'])
        ->middleware('permission:view_open_events')
        ->name('events.open');

    Route::get('/events/open/{eventId}', [OpenEventController::class, 'show'])
        ->middleware('permission:view_open_events')
        ->name('events.open.show');

    Route::post('/events/open/{eventId}/resolve', [OpenEventController::class, 'resolve'])
        ->middleware('permission:resolve_open_events')
        ->name('events.open.resolve');

    Route::post('/events/open/{eventId}/comment', [OpenEventController::class, 'comment'])
        ->middleware('permission:resolve_open_events')
        ->name('events.open.comment');

    Route::get('/events/closed', [OpenEventController::class, 'closed'])
        ->middleware('permission:view_open_events')
        ->name('events.closed');

    Route::get('/events/closed/{eventId}', [OpenEventController::class, 'closedShow'])
        ->middleware('permission:view_open_events')
        ->name('events.closed.show');

    Route::get('/events/review', [EventReviewController::class, 'index'])
        ->middleware('permission:review_detection_events')
        ->name('events.review');

    Route::post('/events/review/{eventId}', [EventReviewController::class, 'store'])
        ->middleware('permission:review_detection_events')
        ->name('events.review.store');

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
        ->middleware(['permission:view_reports', 'activity.log:view_reports,reports,Vista_de_reportes'])
        ->name('reports.index');

    /*
    |--------------------------------------------------------------------------
    | Auditoría
    |--------------------------------------------------------------------------
    */

    Route::get('/activity-logs', [ActivityLogController::class, 'index'])
        ->middleware('permission:view_user_activity_logs')
        ->name('activity-logs.index');

    Route::get('/activity-logs/export/csv', [ActivityLogController::class, 'exportCsv'])
        ->middleware('permission:view_user_activity_logs')
        ->name('activity-logs.export.csv');

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
