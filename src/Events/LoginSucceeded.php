<?php

namespace WikaGroup\SessionService\Events;

use Illuminate\Contracts\Auth\Authenticatable;

class LoginSucceeded
{
    /**
     * @param  array{session_token: string, expires_at: string, user_id: string, email: string, first_name: string, last_name: string, display_name: string}  $userData
     */
    public function __construct(
        public readonly Authenticatable $user,
        public readonly array $userData,
    ) {}
}
