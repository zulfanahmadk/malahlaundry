<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ReceiptDomain
{
    public function handle(Request $request, Closure $next): Response
    {
        $receiptHost = parse_url(config('domains.receipt_url') ?? '', PHP_URL_HOST);
        if ($receiptHost && $request->getHost() === $receiptHost) {
            if ($request->is('/')) {
                return response('Buka tautan nota yang diberikan oleh kasir Malah Laundry.', 200)
                    ->header('Content-Type', 'text/plain; charset=UTF-8')
                    ->header('X-Robots-Tag', 'noindex, nofollow');
            }
            abort_unless($request->is('n/*', 'store/logo', 'up'), 404);
        }

        return $next($request);
    }
}
