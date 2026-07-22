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
     * Throwing the base exception is flagged, including when the throw shares
     * its line with a preceding statement; throwing a domain exception,
     * re-throwing a variable, and a `new` in the statement after a re-throw are
     * not.
     *
     * @return void
     */
    public function testFlagsThrowsOfTheBaseException(): void
    {
        $this->assertErrorsOnLines('DisallowBaseException.inc', [11, 32]);
    }

    /**
     * The rendered message names the thrown class exactly as written in the
     * code, including its leading backslash.
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
     * Overlapping configured entries that both match the same throw report a
     * single error, not one per entry.
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
     * Apply the forbidden-exception override chosen by the current test, if
     * any.
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
