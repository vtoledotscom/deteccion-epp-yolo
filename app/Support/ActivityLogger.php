<?php

namespace App\Support;

use App\Models\User;
use App\Models\UserActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Throwable;

class ActivityLogger
{
    public static function log(
        string $action,
        string $module,
        ?string $description = null,
        ?string $targetType = null,
        int|string|null $targetId = null,
        array $metadata = [],
        ?User $user = null,
        ?Request $request = null,
    ): void {
        try {
            $request ??= request();
            $user ??= Auth::user();

            UserActivityLog::create([
                'user_id' => $user?->id,
                'user_name' => $user?->name,
                'user_email' => $user?->email,
                'user_role' => $user?->role,
                'action' => $action,
                'module' => $module,
                'description' => self::descriptionFor($action, $module, $description, $targetType, $targetId, $metadata, $user),
                'route_name' => $request?->route()?->getName(),
                'url' => $request ? self::safeUrl($request) : null,
                'method' => $request?->method(),
                'ip_address' => $request?->ip(),
                'user_agent' => $request?->userAgent(),
                'target_type' => $targetType,
                'target_id' => $targetId !== null ? (string) $targetId : null,
                'metadata_json' => self::safeMetadata($metadata),
            ]);
        } catch (Throwable) {
            report(new \RuntimeException('No se pudo registrar actividad de usuario.'));
        }
    }

    private static function descriptionFor(
        string $action,
        string $module,
        ?string $description,
        ?string $targetType,
        int|string|null $targetId,
        array $metadata,
        ?User $user,
    ): ?string {
        $actor = self::actorLabel($user);
        $eventId = self::eventLabel($targetId, $metadata);
        $targetEmail = self::targetEmail($metadata);
        $moduleLabel = self::moduleLabel($module);

        return match ($action) {
            'login' => "{$actor} inició sesión",
            'logout' => "{$actor} cerró sesión",
            'unauthorized_access' => "{$actor} intentó acceder a una sección sin permiso",
            'view_dashboard' => "{$actor} revisó el dashboard",
            'view_events' => "{$actor} revisó el listado de eventos",
            'view_event_detail' => $eventId ? "{$actor} revisó evento {$eventId}" : "{$actor} revisó detalle de evento",
            'view_open_events' => "{$actor} revisó eventos abiertos",
            'view_open_event_detail' => $eventId ? "{$actor} revisó evento abierto {$eventId}" : "{$actor} revisó detalle de evento abierto",
            'resolve_open_event' => $eventId ? "{$actor} notificó y cerró el evento {$eventId}" : "{$actor} notificó y cerró un evento",
            'comment_open_event' => $eventId ? "{$actor} comentó el evento {$eventId}" : "{$actor} comentó un evento",
            'view_closed_events' => "{$actor} revisó eventos cerrados",
            'view_closed_event_detail' => $eventId ? "{$actor} revisó cierre del evento {$eventId}" : "{$actor} revisó detalle de evento cerrado",
            'download_event_pdf' => $eventId ? "{$actor} descargó PDF del evento {$eventId}" : "{$actor} descargó PDF de evento",
            'download_evidence' => $eventId ? "{$actor} descargó evidencia del evento {$eventId}" : "{$actor} descargó evidencia de evento",
            'download_csv' => "{$actor} descargó CSV de {$moduleLabel}",
            'download_pdf' => "{$actor} descargó PDF de {$moduleLabel}",
            'view_reports' => "{$actor} revisó reportes",
            'create_user' => $targetEmail ? "{$actor} creó usuario {$targetEmail}" : "{$actor} creó un usuario",
            'update_user' => $targetEmail ? "{$actor} actualizó usuario {$targetEmail}" : "{$actor} actualizó un usuario",
            'activate_user' => $targetEmail ? "{$actor} activó usuario {$targetEmail}" : "{$actor} activó un usuario",
            'disable_user' => $targetEmail ? "{$actor} deshabilitó usuario {$targetEmail}" : "{$actor} deshabilitó un usuario",
            'delete_user' => $targetEmail ? "{$actor} eliminó usuario {$targetEmail}" : "{$actor} eliminó un usuario",
            default => $description,
        };
    }

    private static function actorLabel(?User $user): string
    {
        return match ($user?->role) {
            'admin' => 'Admin',
            'supervisor' => 'Usuario supervisor',
            'operator' => 'Usuario operator',
            'viewer' => 'Usuario viewer',
            default => $user?->name ? 'Usuario ' . $user->name : 'Usuario',
        };
    }

    private static function eventLabel(int|string|null $targetId, array $metadata): ?string
    {
        $eventId = $metadata['display_id'] ?? $targetId;

        return $eventId !== null ? (string) $eventId : null;
    }

    private static function targetEmail(array $metadata): ?string
    {
        $email = $metadata['target_email'] ?? null;

        return $email !== null ? (string) $email : null;
    }

    private static function moduleLabel(string $module): string
    {
        return match ($module) {
            'auth' => 'autenticación',
            'dashboard' => 'dashboard',
            'events' => 'eventos',
            'evidence' => 'evidencias',
            'reports' => 'reportes',
            'security' => 'seguridad',
            'users' => 'usuarios',
            default => str_replace('_', ' ', $module),
        };
    }

    private static function safeUrl(Request $request): string
    {
        return $request->fullUrlWithoutQuery([
            'password',
            'password_confirmation',
            'current_password',
            'token',
            '_token',
            'recovery_code',
            'two_factor_code',
        ]);
    }

    private static function safeMetadata(array $metadata): array
    {
        $blockedKeys = [
            'password',
            'password_confirmation',
            'current_password',
            'token',
            '_token',
            'remember_token',
            'two_factor_secret',
            'two_factor_recovery_codes',
            'recovery_code',
            'two_factor_code',
            'authorization',
            'cookie',
            'secret',
            'api_key',
            'payload',
            'request',
            'body',
        ];

        return collect($metadata)
            ->reject(fn ($value, $key) => self::isBlockedMetadataKey((string) $key, $blockedKeys))
            ->map(function ($value) {
                if (is_array($value)) {
                    return self::safeMetadata($value);
                }

                if (is_bool($value) || is_numeric($value) || $value === null) {
                    return $value;
                }

                return mb_substr((string) $value, 0, 500);
            })
            ->all();
    }

    private static function isBlockedMetadataKey(string $key, array $blockedKeys): bool
    {
        $key = mb_strtolower($key);

        if (in_array($key, $blockedKeys, true)) {
            return true;
        }

        foreach (['password', 'token', 'secret', 'authorization', 'cookie', 'payload', 'request', 'body'] as $fragment) {
            if (str_contains($key, $fragment)) {
                return true;
            }
        }

        return false;
    }
}
