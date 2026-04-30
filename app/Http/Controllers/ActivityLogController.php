<?php

namespace App\Http\Controllers;

use App\Models\UserActivityLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ActivityLogController extends Controller
{
    public function index(Request $request): View
    {
        $allowedRoles = $this->allowedRolesFromRequest($request);
        $filters = $this->filtersFromRequest($request);

        $logs = $this->filteredQuery($filters, $allowedRoles)
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('activity-logs.index', [
            'logs' => $logs,
            'filters' => $filters,
            'roles' => $allowedRoles,
            'actions' => UserActivityLog::query()
                ->whereIn('user_role', $allowedRoles)
                ->select('action')
                ->distinct()
                ->orderBy('action')
                ->pluck('action'),
            'modules' => UserActivityLog::query()
                ->whereIn('user_role', $allowedRoles)
                ->select('module')
                ->distinct()
                ->orderBy('module')
                ->pluck('module'),
        ]);
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $allowedRoles = $this->allowedRolesFromRequest($request);
        $filters = $this->filtersFromRequest($request);
        $filename = 'auditoria_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($request, $filters, $allowedRoles) {
            $handle = fopen('php://output', 'w');

            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($handle, [
                'Fecha',
                'Usuario',
                'Email',
                'Rol',
                'Accion',
                'Modulo',
                'Descripcion',
                'IP',
                'Ruta',
                'Metodo',
            ], ';');

            $this->filteredQuery($filters, $allowedRoles)
                ->orderByDesc('created_at')
                ->chunk(500, function ($logs) use ($handle) {
                    foreach ($logs as $log) {
                        fputcsv($handle, [
                            $this->csvValue($log->created_at?->format('d-m-Y H:i:s')),
                            $this->csvValue($log->user_name),
                            $this->csvValue($log->user_email),
                            $this->csvValue($log->user_role),
                            $this->csvValue($log->action),
                            $this->csvValue($log->module),
                            $this->csvValue($log->description),
                            $this->csvValue($log->ip_address),
                            $this->csvValue($log->route_name),
                            $this->csvValue($log->method),
                        ], ';');
                    }
                });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function allowedRolesFromRequest(Request $request): array
    {
        $allowedRoles = $this->visibleRolesFor((string) $request->user()->role);

        abort_if($allowedRoles === [], Response::HTTP_FORBIDDEN, 'No tienes permisos para acceder a esta sección.');

        return $allowedRoles;
    }

    private function filtersFromRequest(Request $request): array
    {
        return [
            'user' => trim((string) $request->query('user', '')),
            'role' => (string) $request->query('role', ''),
            'action' => trim((string) $request->query('action', '')),
            'module' => trim((string) $request->query('module', '')),
            'date_from' => $this->dateFilter($request->query('date_from')),
            'date_to' => $this->dateFilter($request->query('date_to')),
            'ip' => trim((string) $request->query('ip', '')),
        ];
    }

    private function filteredQuery(array $filters, array $allowedRoles): Builder
    {
        return UserActivityLog::query()
            ->whereIn('user_role', $allowedRoles)
            ->when($filters['user'] !== '', function ($query) use ($filters) {
                $query->where(function ($query) use ($filters) {
                    $query->where('user_name', 'like', '%' . $filters['user'] . '%')
                        ->orWhere('user_email', 'like', '%' . $filters['user'] . '%');
                });
            })
            ->when(in_array($filters['role'], $allowedRoles, true), fn ($query) => $query->where('user_role', $filters['role']))
            ->when($filters['action'] !== '', fn ($query) => $query->where('action', $filters['action']))
            ->when($filters['module'] !== '', fn ($query) => $query->where('module', $filters['module']))
            ->when($filters['date_from'] !== '', fn ($query) => $query->whereDate('created_at', '>=', $filters['date_from']))
            ->when($filters['date_to'] !== '', fn ($query) => $query->whereDate('created_at', '<=', $filters['date_to']))
            ->when($filters['ip'] !== '', fn ($query) => $query->where('ip_address', 'like', '%' . $filters['ip'] . '%'));
    }

    private function dateFilter(mixed $value): string
    {
        $value = (string) $value;

        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : '';
    }

    private function csvValue(mixed $value): string
    {
        $value = str_replace(["\r", "\n"], ' ', (string) $value);
        $value = trim($value);

        if ($value !== '' && in_array($value[0], ['=', '+', '-', '@'], true)) {
            return "'" . $value;
        }

        return $value;
    }

    private function visibleRolesFor(string $role): array
    {
        return match ($role) {
            'admin' => ['supervisor', 'operator', 'viewer'],
            'supervisor' => ['operator', 'viewer'],
            default => [],
        };
    }
}
