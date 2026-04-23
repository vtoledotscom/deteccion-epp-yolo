<?php

namespace App\Http\Controllers;

use App\Models\EppEvent;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EventExportController extends Controller
{
    public function csv(Request $request): StreamedResponse
    {
        $events = $this->buildFilteredQuery($request)
            ->orderByDesc('event_confirmed_at')
            ->get();

        $filename = 'eventos_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($events) {
            $handle = fopen('php://output', 'w');

            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($handle, [
                'ID Evento',
                'Fecha',
                'Cámara',
                'Escenario',
                'Tipo Evento',
                'Estado',
                'Violaciones',
                #'Persona ID',
                #'Frame',
            ], ';');

            foreach ($events as $event) {
                fputcsv($handle, [
                    $event->event_id,
                    optional($event->event_confirmed_at)->format('d-m-Y H:i:s'),
                    $event->camera_id,
                    $event->scenario_id,
                    $event->event_type,
                    $event->status,
                    implode(', ', $event->violation_codes_json ?? []),
                    #$event->person_track_id,
                    #$event->frame_number,
                ], ';');
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function pdf(Request $request)
    {
        $events = $this->buildFilteredQuery($request)
            ->orderByDesc('event_confirmed_at')
            ->get();

        $dateFrom = $request->input('date_from')
            ? Carbon::parse($request->input('date_from'))->startOfDay()
            : now()->subDays(7)->startOfDay();

        $dateTo = $request->input('date_to')
            ? Carbon::parse($request->input('date_to'))->endOfDay()
            : now()->endOfDay();

        $filters = [
            'camera' => $request->input('camera', 'all'),
            'scenario' => $request->input('scenario', 'all'),
            'event_type' => $request->input('event_type', 'all'),
            'status' => $request->input('status', 'all'),
            'search' => $request->input('search', ''),
        ];

        $pdf = Pdf::loadView('events.pdf', [
            'events' => $events,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'filters' => $filters,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('eventos_' . now()->format('Ymd_His') . '.pdf');
    }

    protected function buildFilteredQuery(Request $request)
    {
        $dateFrom = $request->input('date_from')
            ? Carbon::parse($request->input('date_from'))->startOfDay()
            : now()->subDays(7)->startOfDay();

        $dateTo = $request->input('date_to')
            ? Carbon::parse($request->input('date_to'))->endOfDay()
            : now()->endOfDay();

        $query = EppEvent::query()
            ->whereBetween('event_confirmed_at', [$dateFrom, $dateTo]);

        if ($request->filled('camera') && $request->input('camera') !== 'all') {
            $query->where('camera_id', $request->input('camera'));
        }

        if ($request->filled('scenario') && $request->input('scenario') !== 'all') {
            $query->where('scenario_id', $request->input('scenario'));
        }

        if ($request->filled('event_type') && $request->input('event_type') !== 'all') {
            $query->where('event_type', $request->input('event_type'));
        }

        if ($request->filled('status') && $request->input('status') !== 'all') {
            if ($request->input('status') === 'open') {
                $query->where('event_type', 'violation_started')
                    ->whereNull('resolved_by_event_id');
            }

            if ($request->input('status') === 'resolved') {
                $query->where('event_type', 'violation_resolved');
            }
        }

        if ($request->filled('search')) {
            $query->where('event_id', 'like', '%' . $request->input('search') . '%');
        }

        return $query;
    }
}