<?php

namespace App\Http\Middleware;

use App\Services\AuditRecorder;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RecordAuditRequest
{
    public function handle(Request $request, Closure $next): Response
    {
        $actorBefore = $request->user()?->only(['id', 'role']);
        $response = $next($request);
        app(AuditRecorder::class)->record($request, $response, $actorBefore);

        return $response;
    }
}
