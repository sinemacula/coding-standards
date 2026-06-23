<?php

namespace App;

class Handler
{
    public function bare(): void
    {
        try {
            risky();
        } catch (\Throwable $e) {
        }
    }

    public function documented(): void
    {
        try {
            risky();
        } catch (\Throwable) {
            // fall back silently for non-JSON scalars
        }
    }

    public function handled(): void
    {
        try {
            risky();
        } catch (\Throwable $e) {
            throw $e;
        }
    }

    public function bareSameLine(): void
    {
        try {
            risky();
        } catch (\RuntimeException $e) {}
    }
}
