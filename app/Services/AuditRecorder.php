<?php

namespace App\Services;

use App\Logging\FeatureLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class AuditRecorder
{
    public function record(Request $request, Response $response, ?array $actorBefore): void
    {
        $route = $request->route();
        if (! $route) {
            return;
        }
        $uri = $route->uri();
        $login = $request->isMethod('POST') && in_array($uri, ['login', 'api/v1/auth/login'], true);
        $protected = count(array_intersect($route->gatherMiddleware(), ['auth:web', 'auth:sanctum'])) > 0;
        $status = $response->getStatusCode();
        $validationFailed = $request->hasSession()
            && in_array('errors', $request->session()->get('_flash.new', []), true);
        $outcome = $status >= 500 ? 'error' : ($status >= 400 || $validationFailed ? 'rejected' : 'success');
        $actorAfter = $request->attributes->get('audit_actor') ?? $request->user()?->only(['id', 'role']);
        $actor = $login ? ($outcome === 'success' ? $actorAfter : null) : ($actorBefore ?? $actorAfter);
        $readAudit = $request->routeIs('admin.audit.*');
        if (! $login && ! (($actor || $protected) && (
            ! $request->isMethodSafe() || $status >= 400 || $readAudit || str_ends_with($uri, '/export')
        ))) {
            return;
        }

        try {
            DB::table('audit_logs')->insert([
                'occurred_at' => now()->utc(),
                'actor_id' => $actor['id'] ?? null,
                'actor_role' => $actor['role'] ?? null,
                'branch_id' => $request->attributes->get('branch_id'),
                'feature' => FeatureLog::resolve($request),
                // Use the route pattern, never URLs, query strings or submitted data.
                'action' => $uri,
                'method' => $request->method(),
                'channel' => $request->is('api/*') ? 'api' : 'web',
                'status' => $status,
                'outcome' => $outcome,
                'request_id' => $request->attributes->get('log_request_id'),
            ]);
        } catch (Throwable) {
            // A logging failure must not turn an acknowledged POS write into a retry.
            Log::channel('admin-sistem')->error('Pencatatan audit gagal.', [
                'request_id' => $request->attributes->get('log_request_id'),
            ]);
        }
    }
}
