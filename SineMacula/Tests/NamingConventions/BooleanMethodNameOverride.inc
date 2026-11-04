<?php

namespace App;

use Override;

interface Reportable
{
    public function getStatus(): bool;
}

final class StatusReport implements Reportable
{
    #[\Override]
    public function getStatus(): bool
    {
        return true;
    }

    #[Override]
    public function getActive(): bool
    {
        return true;
    }

    #[\ReturnTypeWillChange, \Override]
    public function getEnabled(): bool
    {
        return true;
    }

    #[\Override]
    #[\ReturnTypeWillChange]
    final public static function getVisible(): bool
    {
        return true;
    }

    #[\ReturnTypeWillChange]
    #[OVERRIDE] protected function getShared(): bool
    {
        return true;
    }

    #[\ReturnTypeWillChange]
    public function getHidden(): bool
    {
        return true;
    }

    #[\Deprecated(since: Override::class)]
    public function getLocked(): bool
    {
        return true;
    }

    #[\App\Override]
    public function getOpen(): bool
    {
        return true;
    }

    #[\Override]
    private string $label = '';

    public function getClosed(): bool
    {
        return true;
    }

    #[\Override]function getFresh(): bool
    {
        return true;
    }

    #[\Deprecated(since: '2.0', message: 'Use (the) other, then this'), \Override]
    public function getStale(): bool
    {
        return true;
    }

    #[\Override] // Named by the parent.
    public function getPinned(): bool
    {
        return true;
    }

    #[/* Named by the parent. */ \Override, \ReturnTypeWillChange]
    public function getQueued(): bool
    {
        return true;
    }
}
