<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureWhisperApiToken
{
    /**
     * Allow the request only if it carries the configured whisper API token as bearer token.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $configuredToken = (string) config('services.whisper.token');
        $requestToken = (string) $request->bearerToken();

        if ($configuredToken === '' || ! hash_equals($configuredToken, $requestToken)) {
            abort(Response::HTTP_UNAUTHORIZED, 'Invalid whisper API token.');
        }

        return $next($request);
    }
}
