<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Listado de eventos</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12px;
            color: #1f2a37;
        }

        h1, h2, h3 {
            margin: 0 0 10px;
        }

        .mb-20 {
            margin-bottom: 20px;
        }

        .filters {
            margin-bottom: 20px;
            padding: 10px;
            border: 1px solid #dbe4ee;
            background: #f8fbff;
        }

        .filters p {
            margin: 4px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            border: 1px solid #dbe4ee;
            padding: 8px;
            text-align: left;
            vertical-align: top;
        }

        th {
            background: #eef3f8;
        }
    </style>
</head>
<body>
    @php
        function eventsPdfCameraLabel(string $cameraId): string {
            return strtoupper(str_replace('cam_rtsp_', 'CAM ', $cameraId));
        }

        function eventsPdfScenarioLabel(string $scenarioId): string {
            return match ($scenarioId) {
                'helmet_required' => 'Casco obligatorio',
                'vest_required' => 'Chaleco obligatorio',
                'helmet_and_vest_required' => 'Casco y chaleco',
                default => $scenarioId,
            };
        }

        function eventsPdfEventTypeLabel(string $eventType): string {
            return match ($eventType) {
                'violation_started' => 'Abierto',
                'violation_resolved' => 'Resuelto',
                default => $eventType,
            };
        }

        function eventsPdfStatusLabel(string $status): string {
            return match ($status) {
                'non_compliant' => 'Infracción',
                'compliant' => 'Cumple',
                default => $status,
            };
        }

        function eventsPdfViolationLabels(array $violations, string $eventType): string {
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

        function eventsPdfFilterValue(string $value): string {
            return $value === 'all' || $value === '' ? 'Todos' : $value;
        }
    @endphp

    <div class="mb-20">
        <h1>Listado de eventos EPP</h1>
        <p>Desde: {{ $dateFrom->format('d-m-Y H:i:s') }} | Hasta: {{ $dateTo->format('d-m-Y H:i:s') }}</p>
    </div>

    <div class="filters">
        <h3>Filtros aplicados</h3>
        <p><strong>Cámara:</strong> {{ $filters['camera'] === 'all' ? 'Todas' : eventsPdfCameraLabel($filters['camera']) }}</p>
        <p><strong>Escenario:</strong> {{ $filters['scenario'] === 'all' ? 'Todos' : eventsPdfScenarioLabel($filters['scenario']) }}</p>
        <p><strong>Tipo de evento:</strong> {{ $filters['event_type'] === 'all' ? 'Todos' : eventsPdfEventTypeLabel($filters['event_type']) }}</p>
        <p><strong>Estado:</strong>
            @if($filters['status'] === 'all')
                Todos
            @elseif($filters['status'] === 'open')
                Abiertos
            @elseif($filters['status'] === 'resolved')
                Resueltos
            @else
                {{ $filters['status'] }}
            @endif
        </p>
        <p><strong>Búsqueda:</strong> {{ $filters['search'] ?: 'Sin filtro' }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>ID Evento</th>
                <th>Fecha</th>
                <th>Cámara</th>
                <th>Escenario</th>
                <th>Tipo Evento</th>
                <th>Estado</th>
                <th>Violaciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse($events as $event)
                <tr>
                    <td>{{ $event->event_id }}</td>
                    <td>{{ optional($event->event_confirmed_at)->format('d-m-Y H:i:s') }}</td>
                    <td>{{ eventsPdfCameraLabel($event->camera_id) }}</td>
                    <td>{{ eventsPdfScenarioLabel($event->scenario_id) }}</td>
                    <td>{{ eventsPdfEventTypeLabel($event->event_type) }}</td>
                    <td>{{ eventsPdfStatusLabel($event->status) }}</td>
                    <td>{{ eventsPdfViolationLabels($event->violation_codes_json ?? [], $event->event_type) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9">No hay eventos para los filtros seleccionados.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>