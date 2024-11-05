<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifySignatureRequest
{
    /**
     * Handle an incoming request.
     *
     * @param Closure(Request): (Response) $next
     */
    public function handle(Request $request, Closure $next)
    {
        // Check for required headers or parameters if needed, but skip token validation
        if (!$request->has('required_param')) {
            return response()->json(['message' => 'Required parameter missing.'], 400);
        }

        return $next($request);
    }

}
