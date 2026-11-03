<?php

namespace App;

use App\Errors\ImportedException;

final class Fixture
{
    // Flagged: the body throws a type no tag declares, however many tags.

    /**
     * @throws \InvalidArgumentException
     */
    public function throwsTwoDeclaresOne(int $value): void
    {
        if ($value === 0) {
            throw new \InvalidArgumentException();
        }

        throw new \DomainException();
    }

    /**
     */
    public function throwsWithNoTag(): void
    {
        throw new \RuntimeException();
    }

    /**
     */
    public function throwsTheUnnamedWithNoTag(): void
    {
        throw $this->failure();
    }

    /**
     * @throws \DomainException
     */
    public function rethrowsAnUndeclaredCatch(): void
    {
        try {
            $this->work();
        } catch (\DomainException|\LengthException $exception) {
            throw $exception;
        }
    }

    /**
     * @throws \LogicException
     */
    #[\Deprecated]
    final public static function namesAFactory(): void
    {
        throw Errors\Factory::make();
    }

    /**
     * @return void
     */
    public function throwsWithOnlyOtherTags(): void
    {
        throw new \RuntimeException();
    }

    /**
     * @throws \LogicException
     */
    public function throwsTheSameTypeTwice(int $value): void
    {
        if ($value === 0) {
            throw new \RuntimeException();
        }

        throw new \RuntimeException();
    }

    /**
     * @throws \LogicException
     */
    public function throwsBeforeADeclaredType(int $value): void
    {
        if ($value === 0) {
            throw new \RuntimeException();
        }

        throw new \LogicException();
    }

    /**
     * @throws \LogicException
     */
    public function throwsAfterAClosure(): void
    {
        $closure = static function (): void {
            throw new \LogicException();
        };

        throw new \RuntimeException();
    }

    /**
     * @throws \LogicException
     */
    public function rethrowsAShortNamedCatch(): void
    {
        try {
            $this->work();
        } catch (LengthException $exception) {
            throw $exception;
        }
    }

    /**
     * @throws \App\ImportFailure
     */
    public function throwsANameTheTagOnlyEndsWith(): void
    {
        throw new Failure();
    }

    /**
     * @throws Failure
     */
    public function throwsANameThatOnlyEndsWithTheTag(): void
    {
        throw new \App\Errors\ParseFailure();
    }

    /**
     * @throws \LogicException
     */
    public function writesNoSpaceBeforeTheCaughtVariable(): void
    {
        try {
            $this->work();
        } catch (\DomainException$exception) {
            throw $exception;
        }
    }

    // Not flagged: every type thrown in the body is declared.

    /**
     * @param  string  $payload
     * @return array
     *
     * @throws \InvalidArgumentException
     * @throws \JsonException
     */
    public function throwsAndPropagates(string $payload): array
    {
        if ($payload === '') {
            throw new \InvalidArgumentException();
        }

        return json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @throws \InvalidArgumentException|\DomainException
     */
    public function declaresAUnion(int $value): void
    {
        if ($value === 0) {
            throw new \InvalidArgumentException();
        }

        throw new \DomainException();
    }

    /**
     * @throws \App\Errors\ImportedException
     * @throws ShortException
     * @throws \App\RelativeException
     * @throws \runtimeexception
     */
    public function matchesByTheSharedPart(int $value): void
    {
        if ($value === 0) {
            throw new ImportedException();
        }

        if ($value === 1) {
            throw new \App\Errors\ShortException();
        }

        if ($value === 2) {
            throw new namespace\RelativeException();
        }

        throw new \RuntimeException();
    }

    /**
     * @throws \LogicException
     */
    public function throwsTheUnnamedWithATag(): void
    {
        throw $this->failure();
    }

    /**
     * @throws \DomainException
     */
    public function rethrowsAnotherVariable(): void
    {
        try {
            $this->work();
        } catch (\DomainException $exception) {
            $other = $this->failure();

            throw $other;
        }
    }

    /**
     * @throws \LogicException
     */
    public function rethrowsAVariableTheCatchDoesNotBind(): void
    {
        try {
            $this->work();
        } catch (\LengthException $exception) {
            $other = $this->failure();

            throw $other;
        }
    }

    /**
     */
    public function throwsOnlyInNestedScopes(): object
    {
        $closure = static function (): void {
            throw new \RuntimeException();
        };

        return new class {
            public function run(): void
            {
                throw new \RuntimeException();
            }
        };
    }

    /**
     */
    public function throwsNothing(): void
    {
    }

    public function hasNoDocComment(): void
    {
        throw new \RuntimeException();
    }

    /**
     */
    abstract public function hasNoBody(): void;
}
