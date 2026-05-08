<?php

namespace App\Support;

final class SearchNormalizer
{
    private const USER_ROLE_ALIASES = [
        'admin' => ['admin', 'administrador'],
        'supervisor' => ['supervisor'],
        'operator' => ['operator', 'operador'],
        'viewer' => ['viewer', 'visualizador'],
    ];

    private const EVENT_MANAGEMENT_STATUS_ALIASES = [
        'pending' => ['pending', 'pendiente', 'pendientes', 'abierto', 'abiertos'],
        'resolved' => ['resolved', 'cerrado', 'cerrados', 'resuelto', 'resueltos'],
    ];

    private const EVENT_DETECTED_STATUS_ALIASES = [
        'non_compliant' => ['non_compliant', 'incumplimiento', 'incumple', 'sin epp', 'no cumple'],
        'compliant' => ['compliant', 'cumplimiento', 'cumple', 'correcto epp'],
    ];

    private const EVENT_MANUAL_STATUS_ALIASES = [
        'false_positive' => ['false_positive', 'falso positivo', 'falsa alarma'],
        'correct' => ['correct', 'correcto', 'validado'],
    ];

    private const ACTIVITY_LOG_ACTION_ALIASES = [
        'login' => ['login', 'inicio sesion', 'iniciar sesion', 'ingreso'],
        'logout' => ['logout', 'cierre sesion', 'cerrar sesion', 'salida'],
        'create' => ['create', 'crear', 'creado', 'creacion'],
        'update' => ['update', 'editar', 'actualizado', 'modificacion'],
        'delete' => ['delete', 'eliminar', 'borrado'],
        'download' => ['export', 'exportar', 'descarga', 'descargar', 'download'],
        'view' => ['review', 'revisar', 'validacion', 'validar', 'view'],
    ];

    private const ACTIVITY_LOG_MODULE_ALIASES = [
        'users' => ['users', 'usuarios', 'usuario'],
        'events' => ['events', 'eventos', 'evento'],
        'reports' => ['reports', 'reportes', 'reporte'],
        'dashboard' => ['dashboard', 'panel'],
        'activity_logs' => ['activity_logs', 'auditoria', 'actividad', 'logs'],
    ];

    public static function normalize(string $value): string
    {
        $normalized = trim($value);
        $normalized = function_exists('mb_strtolower')
            ? mb_strtolower($normalized, 'UTF-8')
            : strtolower($normalized);
        $normalized = self::removeAccents($normalized);

        return preg_replace('/\s+/', ' ', $normalized) ?? $normalized;
    }

    /**
     * @param  array<string, array<int, string>>  $aliasesByKey
     * @return array<int, string>
     */
    public static function matchingAliasKeys(string $term, array $aliasesByKey): array
    {
        $normalizedTerm = self::normalize($term);

        if ($normalizedTerm === '') {
            return [];
        }

        $matches = [];

        foreach ($aliasesByKey as $key => $aliases) {
            foreach ($aliases as $alias) {
                $normalizedAlias = self::normalize($alias);

                if (str_starts_with($normalizedAlias, $normalizedTerm)
                    || str_starts_with($normalizedTerm, $normalizedAlias)) {
                    $matches[] = $key;
                    break;
                }
            }
        }

        return array_values(array_unique($matches));
    }

    /**
     * @return array<int, string>
     */
    public static function matchingUserRoles(string $term): array
    {
        return self::matchingAliasKeys($term, self::USER_ROLE_ALIASES);
    }

    /**
     * @return array<int, string>
     */
    public static function matchingEventManagementStatuses(string $term): array
    {
        return self::matchingAliasKeys($term, self::EVENT_MANAGEMENT_STATUS_ALIASES);
    }

    /**
     * @return array<int, string>
     */
    public static function matchingEventDetectedStatuses(string $term): array
    {
        return self::matchingAliasKeys($term, self::EVENT_DETECTED_STATUS_ALIASES);
    }

    /**
     * @return array<int, string>
     */
    public static function matchingEventManualStatuses(string $term): array
    {
        return self::matchingAliasKeys($term, self::EVENT_MANUAL_STATUS_ALIASES);
    }

    /**
     * @return array<int, string>
     */
    public static function matchingActivityLogActionTerms(string $term): array
    {
        return self::matchingAliasKeys($term, self::ACTIVITY_LOG_ACTION_ALIASES);
    }

    /**
     * @return array<int, string>
     */
    public static function matchingActivityLogModules(string $term): array
    {
        return self::matchingAliasKeys($term, self::ACTIVITY_LOG_MODULE_ALIASES);
    }

    public static function eventSequenceIdFromSearch(string $term): ?int
    {
        $normalized = self::normalize($term);

        if (! preg_match('/^(?:evt-?)?0*(\d+)$/', $normalized, $matches)) {
            return null;
        }

        return (int) $matches[1];
    }

    private static function removeAccents(string $value): string
    {
        $search = [
            'á', 'à', 'ä', 'â', 'ã',
            'é', 'è', 'ë', 'ê',
            'í', 'ì', 'ï', 'î',
            'ó', 'ò', 'ö', 'ô', 'õ',
            'ú', 'ù', 'ü', 'û',
            'ñ', 'ç',
        ];

        $replace = [
            'a', 'a', 'a', 'a', 'a',
            'e', 'e', 'e', 'e',
            'i', 'i', 'i', 'i',
            'o', 'o', 'o', 'o', 'o',
            'u', 'u', 'u', 'u',
            'n', 'c',
        ];

        return str_replace($search, $replace, $value);
    }
}
