<?php

namespace App\Support;

final class UiLabelFormatter
{
    private const ROLE_LABELS = [
        'admin' => 'Administrador',
        'supervisor' => 'Supervisor',
        'operator' => 'Operador',
        'viewer' => 'Visualizador',
    ];

    private const ACTION_LABELS = [
        'login' => 'Inicio sesión',
        'logout' => 'Cierre sesión',
        'create' => 'Creación',
        'update' => 'Modificación',
        'delete' => 'Eliminación',
        'export' => 'Exportación',
        'unauthorized_access' => 'Acceso no autorizado',
        'view_dashboard' => 'Ver dashboard',
        'view_events' => 'Ver eventos',
        'view_event_management' => 'Ver gestión operacional',
        'view_event_detail' => 'Ver detalle de evento',
        'view_open_events' => 'Ver eventos abiertos',
        'view_open_event_detail' => 'Ver detalle de evento abierto',
        'view_closed_events' => 'Ver eventos cerrados',
        'view_closed_event_detail' => 'Ver detalle de evento cerrado',
        'view_reports' => 'Ver reportes',
        'review_detection_events' => 'Validación de detecciones',
        'manage_users' => 'Gestión de usuarios',
        'create_user' => 'Creación de usuario',
        'update_user' => 'Modificación de usuario',
        'activate_user' => 'Activación de usuario',
        'disable_user' => 'Deshabilitación de usuario',
        'delete_user' => 'Eliminación de usuario',
        'download_event_pdf' => 'Descarga de PDF de evento',
        'download_csv' => 'Descarga de CSV',
        'download_pdf' => 'Descarga de PDF',
        'download_evidence' => 'Descarga de evidencia',
        'resolve_open_event' => 'Cierre de evento abierto',
        'comment_open_event' => 'Comentario en evento abierto',
        'validate_detection_event' => 'Validación de detección',
    ];

    private const MODULE_LABELS = [
        'users' => 'Usuarios',
        'events' => 'Eventos',
        'open_events' => 'Eventos',
        'closed_events' => 'Eventos',
        'reports' => 'Reportes',
        'dashboard' => 'Dashboard',
        'activity_logs' => 'Auditoría',
        'review' => 'Validación manual',
        'auth' => 'Autenticación',
        'security' => 'Seguridad',
        'evidence' => 'Evidencias',
    ];

    private const DESCRIPTION_ACTION_REPLACEMENTS = [
        'view_closed_events' => 'Ver eventos cerrados',
        'view_open_events' => 'Ver eventos abiertos',
        'download_evidence' => 'Descarga de evidencia',
        'export_csv' => 'Exportación CSV',
        'download_pdf' => 'Descarga PDF',
    ];

    public static function role(?string $role): string
    {
        return self::ROLE_LABELS[$role] ?? self::humanize((string) $role);
    }

    public static function action(?string $action): string
    {
        $action = (string) $action;

        return self::ACTION_LABELS[$action] ?? self::actionByPattern($action);
    }

    public static function module(?string $module): string
    {
        return self::MODULE_LABELS[$module] ?? self::humanize((string) $module);
    }

    /**
     * @param  array<string, string>  $eventDisplayIdMap
     */
    public static function description(?string $description, array $eventDisplayIdMap = []): string
    {
        $description = (string) $description;

        if (trim($description) === '') {
            return 'N/D';
        }

        foreach (self::ROLE_LABELS as $role => $label) {
            $description = preg_replace('/\b' . preg_quote($role, '/') . '\b/i', $label, $description) ?? $description;
        }

        $description = str_replace(
            array_keys(self::DESCRIPTION_ACTION_REPLACEMENTS),
            array_values(self::DESCRIPTION_ACTION_REPLACEMENTS),
            $description
        );

        if ($eventDisplayIdMap === []) {
            return $description;
        }

        return str_replace(
            array_keys($eventDisplayIdMap),
            array_values($eventDisplayIdMap),
            $description
        );
    }

    private static function actionByPattern(string $action): string
    {
        if (str_starts_with($action, 'view_')) {
            return 'Ver ' . self::humanize(substr($action, 5), lowercaseFirst: true);
        }

        if (str_starts_with($action, 'create_')) {
            return 'Creación de ' . self::humanize(substr($action, 7), lowercaseFirst: true);
        }

        if (str_starts_with($action, 'update_')) {
            return 'Modificación de ' . self::humanize(substr($action, 7), lowercaseFirst: true);
        }

        if (str_starts_with($action, 'delete_')) {
            return 'Eliminación de ' . self::humanize(substr($action, 7), lowercaseFirst: true);
        }

        if (str_starts_with($action, 'download_')) {
            return 'Descarga de ' . self::humanize(substr($action, 9), lowercaseFirst: true);
        }

        return self::humanize($action);
    }

    private static function humanize(string $value, bool $lowercaseFirst = false): string
    {
        $label = trim(str_replace('_', ' ', $value));

        if ($label === '') {
            return 'N/D';
        }

        $label = strtolower($label);

        if ($lowercaseFirst) {
            return $label;
        }

        return ucfirst($label);
    }
}
