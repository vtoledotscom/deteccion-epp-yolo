<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Detalle Evento {{ $event->display_id }}</title>
    <style>
    body {
        font-family: Arial, Helvetica, sans-serif;
        font-size: 12px;
        color: #1f2937;
        margin: 24px;
    }

    .header {
        width: 100%;
        border-bottom: 3px solid #0f172a;
        padding-bottom: 14px;
        margin-bottom: 20px;
    }

    .logo {
        width: 135px;
        margin-bottom: 10px;
    }

    h1 {
        font-size: 22px;
        margin: 0;
        color: #111827;
    }

    .subtitle {
        margin-top: 5px;
        color: #4b5563;
    }

    .summary-box {
        border: 1px solid #dbe4ee;
        background: #f8fafc;
        padding: 12px;
        margin-bottom: 18px;
        line-height: 1.5;
    }

    .section {
        margin-bottom: 18px;
    }

    h3 {
        font-size: 15px;
        margin-bottom: 10px;
        color: #111827;
        border-bottom: 1px solid #dbe4ee;
        padding-bottom: 5px;
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
        width: 32%;
        background: #eef3f8;
        color: #1f2937;
    }

    .status-badge {
        display: inline-block;
        padding: 5px 10px;
        border-radius: 12px;
        font-weight: bold;
        color: #ffffff;
    }

    .status-ok {
        background: #15803d;
    }

    .status-error {
        background: #b91c1c;
    }

    .evidence-image {
        width: 100%;
        max-height: 470px;
        object-fit: contain;
        border: 1px solid #dbe4ee;
        padding: 4px;
    }

    .placeholder {
        border: 1px solid #dbe4ee;
        background: #f8fafc;
        padding: 14px;
        color: #6b7280;
    }

    .footer {
        margin-top: 22px;
        padding-top: 10px;
        border-top: 1px solid #dbe4ee;
        color: #6b7280;
        font-size: 10px;
    }
</style>
</head>
<body>
@php
    $logoPath = public_path('images/logotscom.png');
    function pdfEventCameraLabel(string $cameraId): string {
        return strtoupper(str_replace('cam_rtsp_', 'CAM ', $cameraId));
    }

    function pdfEventScenarioLabel(string $scenarioId): string {
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

    function pdfEventStatusLabel(string $status): string {
        return match ($status) {
            'non_compliant' => 'Infracción',
            'compliant' => 'Cumple',
            default => $status,
        };
    }

    function pdfEventViolationLabels(array $violations, string $eventType): string {
        if ($eventType === 'violation_resolved') {
            return 'Resuelto';
        }

        if (empty($violations)) {
            return 'Sin violaciones';
        }

        return implode(', ', array_map(function ($item) {
            return match ($item) {
                'missing_helmet' => 'Sin casco',
                'missing_vest' => 'Sin chaleco',
                default => $item,
            };
        }, $violations));
    }

    $imagePath = null;

    if (optional($event->evidence)->image_annotated_path) {
        $relativePath = ltrim(str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $event->evidence->image_annotated_path), DIRECTORY_SEPARATOR);
        $imagePath = rtrim(config('epp.project_base_path'), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $relativePath;
    }
@endphp

<div class="header">
    @if(file_exists($logoPath))
        <img src="{{ $logoPath }}" class="logo">
    @endif
    <h1>Reporte de Incidente EPP</h1>
    <p class="subtitle">
        Sistema automático de detección de uso de elementos de protección personal.
    </p>
    <p><strong>ID:</strong> {{ $event->display_id }}</p>
    <p><strong>Fecha generación PDF:</strong> {{ now()->format('d-m-Y H:i:s') }}</p>
</div>

<div class="section">
    <h3>Información del evento</h3>

    <table>
        <tr>
            <th>ID interno</th>
            <td>{{ $event->event_id }}</td>
        </tr>
        <tr>
            <th>Fecha detección</th>
            <td>{{ optional($event->event_confirmed_at)->format('d-m-Y H:i:s') }}</td>
        </tr>
        <tr>
            <th>Cámara</th>
            <td>{{ pdfEventCameraLabel($event->camera_id) }}</td>
        </tr>
        <tr>
            <th>Escenario</th>
            <td>{{ pdfEventScenarioLabel($event->scenario_id) }}</td>
        </tr>
        <tr>
            <th>Zona</th>
            <td>{{ $event->zone_name }}</td>
        </tr>
        <tr>
            <th>Resultado</th>
            <td>{{ pdfEventTypeLabel($event->event_type) }}</td>
        </tr>
        <tr>
            <th>Estado</th>
            <td>
                @if($event->status === 'non_compliant')
                    <span class="status-badge status-error">No cumple</span>
                @else
                    <span class="status-badge status-ok">Cumple</span>
                @endif
            </td>
        </tr>
        <tr>
            <th>Violaciones</th>
            <td>{{ pdfEventViolationLabels($event->violation_codes_json ?? [], $event->event_type) }}</td>
        </tr>
    </table>
</div>

<div class="section">
    <h3>Evidencia</h3>

    @if($imagePath && file_exists($imagePath))
        <img src="{{ $imagePath }}" class="evidence-image">
    @else
        <div class="box">
            Imagen de evidencia no disponible.
        </div>
    @endif
</div>

</body>
</html>