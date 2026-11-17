<?php

declare(strict_types = 1);

namespace SineMacula\Tests\Commenting;

use PHPUnit\Framework\Attributes\CoversClass;
use SineMacula\Sniffs\Commenting\RequireThrowsTagSniff;
use SineMacula\Tests\AbstractSniffTestCase;

/**
 * Tests for the throws tag sniff.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 *
 * @internal
 */
#[CoversClass(RequireThrowsTagSniff::class)]
final class RequireThrowsTagSniffTest extends AbstractSniffTestCase
{
    /** @var string The message rendered when the comment has no @throws tag. */
    private const string UNTAGGED = 'Missing @throws tag in function comment';

    /** @var string The message rendered for an undeclared runtime exception. */
    private const string UNDECLARED_RUNTIME = 'Missing @throws tag for "RuntimeException" exception';

    /**
     * Each type thrown directly in the body without a matching tag is flagged
     * once.
     *
     * @return void
     */
    public function testFlagsEachThrownTypeWithoutATag(): void
    {
        $this->assertErrorMessagesOnLines('RequireThrowsTag.inc', [
            13  => ['Missing @throws tag for "DomainException" exception'],
            24  => [self::UNTAGGED],
            31  => [self::UNTAGGED],
            39  => ['Missing @throws tag for "LengthException" exception'],
            51  => ['Missing @throws tag for "Errors\Factory" exception'],
            60  => [self::UNTAGGED],
            68  => [self::UNDECLARED_RUNTIME],
            80  => [self::UNDECLARED_RUNTIME],
            92  => [self::UNDECLARED_RUNTIME],
            104 => ['Missing @throws tag for "LengthException" exception'],
            116 => ['Missing @throws tag for "Failure" exception'],
            124 => ['Missing @throws tag for "App\Errors\ParseFailure" exception'],
            132 => ['Missing @throws tag for "DomainException" exception'],
            144 => [self::UNDECLARED_RUNTIME],
        ]);
    }

    /**
     * Every finding is reported under the one code.
     *
     * @return void
     */
    public function testReportsUnderTheMissingCode(): void
    {
        $this->assertErrorCodesOnLines(
            'RequireThrowsTag.inc',
            array_fill_keys([13, 24, 31, 39, 51, 60, 68, 80, 92, 104, 116, 124, 132, 144], ['Missing']),
        );
    }
}
