<?php

namespace WikaGroup\SessionService\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use WikaGroup\SessionService\SessionService;

class SessionServiceController
{
    public function callback(Request $request, SessionService $service): RedirectResponse
    {
        try {
            $validated = $request->validate(['code' => 'required|string']);
        } catch (ValidationException $e) {
            logger()->warning('Session service callback request is not valid', [
                'errors' => $e->errors(),
                'method' => $request->method(),
            ]);

            abort(400, 'Invalid request.');
        }

        $data = $service->exchangeCode($validated['code']);

        $user = User::where('oauth_id', $data['user_id'])->first()
            ?? User::where('email', $data['email'])->first();

        if ($user && !$user->{'oauth_id'}) {
            $user->update(['oauth_id' => $data['user_id']]);
        }

        if (!$user) {
            $user = User::create([
                'name' => $data['display_name'],
                'email' => $data['email'],
                'oauth_id' => $data['user_id'],
            ]);
        }

        Auth::login($user);

        return redirect()->intended(config('sessionservice.routes.home'));
    }

    public function logout(Request $request, SessionService $service): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect($service->getLogoutUrl());
    }
}
