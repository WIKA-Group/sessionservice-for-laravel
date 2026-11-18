<?php

namespace WikaGroup\SessionService\Events;

use Illuminate\Contracts\Auth\Authenticatable;
use Throwable;

class LogoutFailed
{
    public function __construct(
        public readonly string $message,
        public readonly ?Authenticatable $user = null,
        public readonly ?Throwable $exception = null,
    ) {}
}
