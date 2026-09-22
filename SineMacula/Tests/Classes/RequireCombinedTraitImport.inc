<?php

namespace App;

use App\Concerns\Alpha;
use App\Concerns\Beta;

final class Combined
{
    use Alpha, Beta;
}

final class Single
{
    use Alpha;
}

final class Split
{
    use Alpha;
    use Beta;
}

final class Separated
{
    use Alpha;

    public const int LIMIT = 1;

    use Beta;

    public function run(): void
    {
    }

    use Gamma;
}

final class Tight
{use Alpha;
    use Beta;
}

final class ClosureHolder
{
    use Alpha;

    public function run(): void
    {
        $value = 1;

        $closure = function () use ($value): int {
            return $value;
        };

        $closure();
    }

    use Beta;
}

trait Composed
{
    use Alpha;
    use Beta;
}

enum Status: string
{
    use Alpha;
    use Beta;

    case ACTIVE = 'active';
}

final class Outer
{
    use Alpha, Beta;

    public function make(): object
    {
        return new class
        {
            use Alpha;
            use Beta;
        };
    }
}

interface Contract
{
    public function run(): void;
}

// A declaration the tokenizer never finds a body for.
final class Unterminated
