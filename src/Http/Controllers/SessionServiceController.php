<?php

namespace WikaGroup\SessionService\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Throwable;
use WikaGroup\SessionService\Events\LoginFailed;
use WikaGroup\SessionService\Events\LoginSucceeded;
use WikaGroup\SessionService\Events\LogoutFailed;
use WikaGroup\SessionService\Events\LogoutSucceeded;
use WikaGroup\SessionService\SessionService;

class SessionServiceController
{
    public const string SESSION_USER_DATA_KEY = 'sessionservice-user-data';

    public function callback(Request $request, SessionService $service): RedirectResponse
    {
        try {
            $validated = $request->validate(['code' => 'required|string']);
        } catch (ValidationException $e) {
            logger()->warning('Session service callback request is not valid', [
                'errors' => $e->errors(),
                'method' => $request->method(),
            ]);

            event(new LoginFailed('Invalid request.', $e));

            abort(400, 'Invalid request.');
        }

        try {
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
        } catch (Throwable $e) {
            event(new LoginFailed($e->getMessage(), $e));

            throw $e;
        }

        $request->session()->put(self::SESSION_USER_DATA_KEY, $data);

        event(new LoginSucceeded($user, $data));

        return redirect()->intended(config('sessionservice.routes.home'));
    }

    public function logout(Request $request, SessionService $service): RedirectResponse
    {
        $user = Auth::user();

        try {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        } catch (Throwable $e) {
            event(new LogoutFailed($e->getMessage(), $user, $e));

            throw $e;
        }

        event(new LogoutSucceeded($user));

        return redirect($service->getLogoutUrl());
    }
}
