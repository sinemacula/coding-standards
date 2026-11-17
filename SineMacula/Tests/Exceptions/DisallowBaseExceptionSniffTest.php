<?php

declare(strict_types = 1);

namespace SineMacula\Tests\Exceptions;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversTrait;
use SineMacula\CodingStandards\Sniffs\Concerns\ResolvesQualifiedNames;
use SineMacula\Sniffs\Exceptions\DisallowBaseExceptionSniff;
use SineMacula\Tests\AbstractSniffTestCase;

/**
 * Tests for the base exception throw sniff.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 *
 * @internal
 */
#[CoversClass(DisallowBaseExceptionSniff::class)]
#[CoversTrait(ResolvesQualifiedNames::class)]
final class DisallowBaseExceptionSniffTest extends AbstractSniffTestCase
{
    /** @var string The message rendered for a throw of the base exception. */
    private const string BASE_EXCEPTION_MESSAGE = 'Throw a domain exception, not the base "\Exception".';

    /** @var array<int, string>|null Per-test forbidden-exception override. */
    private ?array $forbiddenExceptions = null;

    /**
     * Flags throwing the base exception, not a domain one or a re-throw.
     *
     * @return void
     */
    public function testFlagsThrowsOfTheBaseException(): void
    {
        $this->assertErrorsOnLines('DisallowBaseException.inc', [11, 32]);
    }

    /**
     * The message names the thrown class as written in the code.
     *
     * @return void
     */
    public function testRendersTheThrownNameInTheMessage(): void
    {
        $this->assertErrorMessagesOnLines('DisallowBaseException.inc', [
            11 => [self::BASE_EXCEPTION_MESSAGE],
            32 => [self::BASE_EXCEPTION_MESSAGE],
        ]);
    }

    /**
     * A configured name with a leading backslash matches the thrown class, and
     * the configured list replaces the default rather than extending it.
     *
     * @return void
     */
    public function testMatchesConfiguredNamesWithALeadingBackslash(): void
    {
        $this->forbiddenExceptions = [\RuntimeException::class];

        $this->assertErrorsOnLines('DisallowBaseExceptionConfigured.inc', [9]);
    }

    /**
     * Overlapping configured entries report one error per throw.
     *
     * @return void
     */
    public function testReportsASingleErrorForOverlappingConfiguredEntries(): void
    {
        $this->forbiddenExceptions = [\Exception::class, \Exception::class];

        $this->assertErrorMessagesOnLines('DisallowBaseExceptionConfigured.inc', [
            14 => [self::BASE_EXCEPTION_MESSAGE],
        ]);
    }

    /**
     * Apply the current test's forbidden-exception override, if any.
     *
     * @return array<string, mixed>
     */
    #[\Override]
    protected function sniffProperties(): array
    {
        return $this->forbiddenExceptions === null
            ? []
            : ['forbiddenExceptions' => $this->forbiddenExceptions];
    }
}
