<?php

namespace WikaGroup\SessionService\Events;

use Illuminate\Contracts\Auth\Authenticatable;

class LogoutSucceeded
{
    public function __construct(
        public readonly ?Authenticatable $user,
    ) {}
}
