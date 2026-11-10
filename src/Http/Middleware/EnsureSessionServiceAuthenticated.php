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

        $request->session()->put('url.intended', $request->fullUrl());

        return redirect(app(SessionService::class)->getLoginUrl());
    }
}
