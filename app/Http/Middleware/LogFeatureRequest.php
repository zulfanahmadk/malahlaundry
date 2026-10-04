<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class LogFeatureRequest
{
    public function handle(Request $request, Closure $next): Response
    {
        $request->attributes->set('log_request_id', (string) Str::uuid());
        $started = hrtime(true);
        $response = $next($request);
        $response->headers->set('X-Request-ID', $request->attributes->get('log_request_id'));

        // Record writes, exports and unsuccessful requests without logging customer input.
        if (! $request->isMethodSafe() || $response->getStatusCode() >= 400 || str_ends_with($request->route()?->uri() ?? '', '/export')) {
            $level = $response->getStatusCode() >= 500 ? 'error' : ($response->getStatusCode() >= 400 ? 'warning' : 'info');
            Log::log($level, 'Permintaan fitur selesai', [
                'status' => $response->getStatusCode(),
                'duration_ms' => round((hrtime(true) - $started) / 1_000_000, 2),
                'user_id' => $request->user()?->id,
                'branch_id' => $request->attributes->get('branch_id'),
            ]);
        }

        return $response;
    }
}
