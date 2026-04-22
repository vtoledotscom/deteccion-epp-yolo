<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de eventos</title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; font-size: 12px; color: #1f2a37; }
        h1, h2, h3 { margin: 0 0 10px; }
        .mb-20 { margin-bottom: 20px; }
        .summary { width: 100%; margin-bottom: 20px; border-collapse: collapse; }
        .summary td { border: 1px solid #dbe4ee; padding: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #dbe4ee; padding: 8px; text-align: left; vertical-align: top; }
        th { background: #eef3f8; }
    </style>
</head>
<body>
    @php
        function pdfCameraLabel(string $cameraId): string {
            return strtoupper(str_replace('cam_rtsp_', 'CAM ', $cameraId));
        }

        function pdfScenarioLabel(string $scenarioId): string {
            return match ($scenarioId) {
                'helmet_required' => 'Casco obligatorio',
                'vest_required' => 'Chaleco obligatorio',
                'helmet_and_vest_required' => 'Casco y chaleco',
                default => $scenarioId,
            };
        }

        function pdfEventTypeLabel(string $eventType): string {
            return match ($eventType) {
                'violation_started' => 'Abierto',
                'violation_resolved' => 'Resuelto',
                default => $eventType,
            };
        }

        function pdfViolationLabels(array $violations, string $eventType): string {
            if ($eventType === 'violation_resolved') {
                return 'Resuelto';
            }

            if (empty($violations)) {
                return 'Sin violaciones';
            }

            $labels = array_map(function ($item) {
                return match ($item) {
                    'missing_helmet' => 'Sin casco',
                    'missing_vest' => 'Sin chaleco',
                    default => $item,
                };
            }, $violations);

            return implode(', ', $labels);
        }
    @endphp

    <div class="mb-20">
        <h1>Reporte de eventos EPP</h1>
        <p>Desde: {{ $dateFrom->format('d-m-Y H:i:s') }} | Hasta: {{ $dateTo->format('d-m-Y H:i:s') }}</p>
    </div>

    <table class="summary">
        <tr>
            <td><strong>Total eventos</strong><br>{{ number_format($summary['total_events'], 0, ',', '.') }}</td>
            <td><strong>Infracciones iniciadas</strong><br>{{ number_format($summary['started_violations'], 0, ',', '.') }}</td>
            <td><strong>Infracciones resueltas</strong><br>{{ number_format($summary['resolved_violations'], 0, ',', '.') }}</td>
            <td><strong>Eventos abiertos</strong><br>{{ number_format($summary['open_violations'], 0, ',', '.') }}</td>
        </tr>
    </table>

    <h3>Listado detallado</h3>

    <table>
        <thead>
            <tr>
                <th>ID Evento</th>
                <th>Fecha</th>
                <th>Cámara</th>
                <th>Escenario</th>
                <th>Tipo</th>
                <th>Violaciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse($events as $event)
                <tr>
                    <td>{{ $event->event_id }}</td>
                    <td>{{ optional($event->event_confirmed_at)->format('d-m-Y H:i:s') }}</td>
                    <td>{{ pdfCameraLabel($event->camera_id) }}</td>
                    <td>{{ pdfScenarioLabel($event->scenario_id) }}</td>
                    <td>{{ pdfEventTypeLabel($event->event_type) }}</td>
                    <td>{{ pdfViolationLabels($event->violation_codes_json ?? [], $event->event_type) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">No hay eventos para el período seleccionado.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>