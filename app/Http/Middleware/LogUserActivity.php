<?php

namespace App\Http\Middleware;

use App\Support\ActivityLogger;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogUserActivity
{
    public function handle(Request $request, Closure $next, string $action, string $module, ?string $description = null): Response
    {
        $response = $next($request);

        if ($request->user() && $response->getStatusCode() < 400) {
            ActivityLogger::log(
                $action,
                $module,
                $description ? str_replace('_', ' ', $description) : null,
                request: $request,
            );
        }

        return $response;
    }
}
