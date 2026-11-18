<?php

namespace WikaGroup\SessionService\Events;

use Throwable;

class LoginFailed
{
    public function __construct(
        public readonly string $message,
        public readonly ?Throwable $exception = null,
    ) {}
}
