<?php

namespace App;

use App\Exceptions\UserNotFound;

class Service
{
    public function find(): void
    {
        throw new \Exception('boom');
    }

    public function load(): void
    {
        throw new UserNotFound('missing');
    }

    public function rethrow(\Throwable $e): void
    {
        throw $e;
    }
}
