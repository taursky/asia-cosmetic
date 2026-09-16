<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyOneCSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        $configuredToken = (string) config('onec.token', '');
        $bearer = (string) $request->bearerToken();

        if ($configuredToken !== '' && $bearer !== '' && hash_equals($configuredToken, $bearer)) {
            return $next($request);
        }

        $secret = (string) config('onec.secret', '');
        $expectedClient = (string) config('onec.client_id', '');
        $client = (string) $request->header('X-1C-Client', '');
        $timestamp = (string) $request->header('X-1C-Timestamp', '');
        $signature = strtolower((string) $request->header('X-1C-Signature', ''));

        if ($secret === '' || $client === '' || $timestamp === '' || $signature === '') {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        if ($expectedClient !== '' && ! hash_equals($expectedClient, $client)) {
            return response()->json(['message' => 'Unknown 1C client.'], 401);
        }

        if (! ctype_digit($timestamp)) {
            return response()->json(['message' => 'Invalid timestamp.'], 401);
        }

        $maxSkew = (int) config('onec.max_clock_skew', 300);
        if (abs(now()->timestamp - (int) $timestamp) > $maxSkew) {
            return response()->json(['message' => 'Request timestamp expired.'], 401);
        }

        $canonical = implode("\n", [
            $timestamp,
            strtoupper($request->method()),
            '/' . ltrim($request->path(), '/'),
            $request->getContent(),
        ]);

        $expected = hash_hmac('sha256', $canonical, $secret);

        if (! hash_equals($expected, $signature)) {
            return response()->json(['message' => 'Invalid signature.'], 401);
        }

        return $next($request);
    }
}
