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

    public function guarded(bool $ok, \Throwable $e): void
    {
        if (!$ok) { throw $e; }
        $audit = new \Exception('recorded, not thrown');
    }

    public function inline(): void
    {
        risky();throw new \Exception('compact');
    }
}
