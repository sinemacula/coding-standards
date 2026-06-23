<?php

namespace App;

class Example
{
    public function isValid(): bool
    {
        return true;
    }

    public function hasErrors(): bool
    {
        return false;
    }

    public function canProcess(): bool
    {
        return true;
    }

    public function permitsFilter(): bool
    {
        return true;
    }

    public function passesGuards(): bool
    {
        return true;
    }

    public function execute(): bool
    {
        return true;
    }

    public function persist(): bool
    {
        return true;
    }

    public function handle(): bool
    {
        return true;
    }

    public function frobnicate(): bool
    {
        return true;
    }

    /**
     * @imperative reconcile performs an action and reports success
     */
    public function reconcile(): bool
    {
        return true;
    }

    public function negated(): bool
    {
        return false;
    }

    public function succeeded(): bool
    {
        return true;
    }

    public function process(): void
    {
    }

    public function count(): int
    {
        return 0;
    }

    public function __isset(string $name): bool
    {
        return false;
    }

    /**
     * Reports whether the system is ready, but without the opt-out tag.
     */
    public function ready(): bool
    {
        return true;
    }

    public function successful(): bool
    {
        return true;
    }
}
