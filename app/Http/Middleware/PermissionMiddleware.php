<?php

namespace App\Http\Middleware;

use App\Support\ActivityLogger;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class PermissionMiddleware
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = Auth::user();

        if (! $user) {
            return redirect()->route('login');
        }

        if (! $user->hasPermission($permission)) {
            ActivityLogger::log(
                'unauthorized_access',
                'security',
                'Intento de acceso no autorizado',
                metadata: [
                    'required_permission' => $permission,
                ],
                user: $user,
                request: $request,
            );

            abort(403, 'No tienes permisos para acceder a esta sección.');
        }

        return $next($request);
    }
}
