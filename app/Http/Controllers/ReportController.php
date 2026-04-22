<?php

namespace App\Http\Controllers;

use App\Models\EppEvent;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        [$dateFrom, $dateTo] = $this->resolveDates($request);

        $query = $this->buildBaseQuery($request, $dateFrom, $dateTo);

        $events = (clone $query)
            ->orderByDesc('event_confirmed_at')
            ->limit(100)
            ->get();

        $summary = [
            'total_events' => (clone $query)->count(),
            'started_violations' => (clone $query)->where('event_type', 'violation_started')->count(),
            'resolved_violations' => (clone $query)->where('event_type', 'violation_resolved')->count(),
            'open_violations' => EppEvent::query()
                ->where('event_type', 'violation_started')
                ->whereNull('resolved_by_event_id')
                ->count(),
        ];

        $scenarioSummary = (clone $query)
            ->selectRaw('scenario_id, COUNT(*) as total')
            ->groupBy('scenario_id')
            ->orderByDesc('total')
            ->get();

        $cameraSummary = (clone $query)
            ->selectRaw('camera_id, COUNT(*) as total')
            ->groupBy('camera_id')
            ->orderByDesc('total')
            ->get();

        return view('reports.index', [
            'dateFrom' => $dateFrom->format('Y-m-d'),
            'dateTo' => $dateTo->format('Y-m-d'),
            'filters' => [
                'camera' => $request->string('camera', 'all')->toString(),
                'scenario' => $request->string('scenario', 'all')->toString(),
                'event_type' => $request->string('event_type', 'all')->toString(),
            ],
            'summary' => $summary,
            'events' => $events,
            'scenarioSummary' => $scenarioSummary,
            'cameraSummary' => $cameraSummary,
            'cameras' => EppEvent::select('camera_id')->distinct()->pluck('camera_id'),
            'scenarios' => EppEvent::select('scenario_id')->distinct()->pluck('scenario_id'),
        ]);
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        [$dateFrom, $dateTo] = $this->resolveDates($request);

        $events = $this->buildBaseQuery($request, $dateFrom, $dateTo)
            ->orderByDesc('event_confirmed_at')
            ->get();

        $filename = 'reporte_eventos_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($events) {
            $handle = fopen('php://output', 'w');

            // BOM UTF-8 para Excel
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($handle, [
                'ID Evento',
                'Fecha',
                'Cámara',
                'Escenario',
                'Tipo Evento',
                'Estado',
                'Violaciones',
                'Persona ID',
                'Frame',
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
                    $event->person_track_id,
                    $event->frame_number,
                ], ';');
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function exportPdf(Request $request)
    {
        [$dateFrom, $dateTo] = $this->resolveDates($request);

        $query = $this->buildBaseQuery($request, $dateFrom, $dateTo);

        $events = (clone $query)
            ->orderByDesc('event_confirmed_at')
            ->get();

        $summary = [
            'total_events' => (clone $query)->count(),
            'started_violations' => (clone $query)->where('event_type', 'violation_started')->count(),
            'resolved_violations' => (clone $query)->where('event_type', 'violation_resolved')->count(),
            'open_violations' => EppEvent::query()
                ->where('event_type', 'violation_started')
                ->whereNull('resolved_by_event_id')
                ->count(),
        ];

        $pdf = Pdf::loadView('reports.pdf', [
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'events' => $events,
            'summary' => $summary,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('reporte_eventos_' . now()->format('Ymd_His') . '.pdf');
    }

    protected function resolveDates(Request $request): array
    {
        $dateFrom = $request->input('date_from')
            ? Carbon::parse($request->input('date_from'))->startOfDay()
            : now()->subDays(7)->startOfDay();

        $dateTo = $request->input('date_to')
            ? Carbon::parse($request->input('date_to'))->endOfDay()
            : now()->endOfDay();

        return [$dateFrom, $dateTo];
    }

    protected function buildBaseQuery(Request $request, $dateFrom, $dateTo)
    {
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

        return $query;
    }
}