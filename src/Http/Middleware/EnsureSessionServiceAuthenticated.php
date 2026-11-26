<?php

namespace WikaGroup\SessionService\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use WikaGroup\SessionService\SessionService;

class EnsureSessionServiceAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            return $next($request);
        }

        // Cross-origin redirects can't be followed by XHR/fetch (CORS) -> let the client navigate top-level.
        if ($request->header('X-Inertia') || $request->expectsJson()) {
            $loginUrl = route('sessionservice.login', array_filter([
                'return' => $request->header('X-Inertia') ? $request->fullUrl() : $request->headers->get('referer'),
            ]));

            if ($request->header('X-Inertia')) {
                return response('', 409)->header('X-Inertia-Location', $loginUrl);
            }

            return response()->json(['message' => 'Unauthenticated.', 'login_url' => $loginUrl], 401);
        }

        $request->session()->put('url.intended', $request->fullUrl());

        return redirect(app(SessionService::class)->getLoginUrl());
    }
}
