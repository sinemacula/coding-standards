<?php

declare(strict_types = 1);

namespace SineMacula\Tests\Commenting;

use PHPUnit\Framework\Attributes\CoversClass;
use SineMacula\Sniffs\Commenting\RequireParamTagSniff;
use SineMacula\Tests\AbstractSniffTestCase;

/**
 * Tests for the param tag sniff.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 *
 * @internal
 */
#[CoversClass(RequireParamTagSniff::class)]
final class RequireParamTagSniffTest extends AbstractSniffTestCase
{
    /** @var string The message rendered when the parameter has no tag. */
    private const string MISSING_VALUE = 'Doc comment for parameter "$value" missing';

    /** @var string The message rendered when the parameter has no tag. */
    private const string MISSING_ROW = 'Doc comment for parameter "$row" missing';

    /** @var string The message rendered for a tag that names no variable. */
    private const string UNNAMED = 'Missing parameter name';

    /** @var string The message rendered for a tag past the last parameter. */
    private const string SURPLUS = 'Superfluous parameter comment';

    /**
     * A parameter with no tag, a tag naming no variable, another variable or
     * the right one in another case, and a tag past the last parameter are each
     * flagged in the core sniff's words, however many lines the type spans. A
     * type the core sniff reports as missing leaves its parameter undocumented,
     * as does a comment that only inherits; abstract, interface and promoted
     * constructor parameters are held to the same rule.
     *
     * @return void
     */
    public function testFlagsEachParameterTheTagsDoNotName(): void
    {
        $this->assertErrorMessagesOnLines('RequireParamTag.inc', [
            9   => ['Doc comment for parameter "$second" missing'],
            14  => ['Doc comment for parameter "$right" missing'],
            15  => ['Doc comment for parameter $wrong does not match actual variable name $right'],
            19  => [self::MISSING_VALUE],
            20  => ['Doc comment for parameter $Value does not match case of actual variable name $value'],
            24  => [self::MISSING_VALUE],
            25  => [self::UNNAMED],
            29  => [self::MISSING_ROW],
            30  => [self::UNNAMED],
            36  => [self::MISSING_ROW],
            37  => ['Doc comment for parameter $wrong does not match actual variable name $row'],
            45  => [self::SURPLUS],
            46  => [self::SURPLUS],
            53  => ['Doc comment for parameter $row is prefixed with "&" but parameter is not passed by reference'],
            59  => ['Doc comment for parameter "...$values" missing'],
            60  => ['Doc comment for parameter $values does not match actual variable name ...$values'],
            65  => ['Doc comment for parameter $second does not match actual variable name $first'],
            66  => ['Doc comment for parameter $first does not match actual variable name $second'],
            70  => [self::MISSING_VALUE],
            75  => [self::MISSING_VALUE],
            80  => ['Doc comment for parameter "$callback" missing'],
            81  => [self::UNNAMED],
            86  => [self::MISSING_VALUE],
            91  => [self::MISSING_VALUE],
            179 => ['Doc comment for parameter "$count" missing'],
            192 => [self::MISSING_VALUE],
        ]);
    }

    /**
     * Each finding is reported under the core sniff's code for it.
     *
     * @return void
     */
    public function testReportsUnderTheCoreSniffsCodes(): void
    {
        $this->assertErrorCodesOnLines('RequireParamTag.inc', [
            9   => ['MissingParamTag'],
            14  => ['MissingParamTag'],
            15  => ['ParamNameNoMatch'],
            19  => ['MissingParamTag'],
            20  => ['ParamNameNoCaseMatch'],
            24  => ['MissingParamTag'],
            25  => ['MissingParamName'],
            29  => ['MissingParamTag'],
            30  => ['MissingParamName'],
            36  => ['MissingParamTag'],
            37  => ['ParamNameNoMatch'],
            45  => ['ExtraParamComment'],
            46  => ['ExtraParamComment'],
            53  => ['ParamNameUnexpectedAmpersandPrefix'],
            59  => ['MissingParamTag'],
            60  => ['ParamNameNoMatch'],
            65  => ['ParamNameNoMatch'],
            66  => ['ParamNameNoMatch'],
            70  => ['MissingParamTag'],
            75  => ['MissingParamTag'],
            80  => ['MissingParamTag'],
            81  => ['MissingParamName'],
            86  => ['MissingParamTag'],
            91  => ['MissingParamTag'],
            179 => ['MissingParamTag'],
            192 => ['MissingParamTag'],
        ]);
    }

    /**
     * An ampersand is reported only on a tag that otherwise names the
     * parameter, and is not counted against the name it prefixes. A name that
     * starts with `this` is a variable, not a type, and a line comment is not
     * read as a doc comment.
     *
     * @return void
     */
    public function testReadsThePrefixesOfAName(): void
    {
        $this->assertErrorMessagesOnLines('RequireParamTagEdges.inc', [
            3  => [self::MISSING_VALUE],
            4  => ['Doc comment for parameter $other does not match actual variable name $value'],
            9  => ['Doc comment for parameter $value is prefixed with "&" but parameter is not passed by reference'],
            10 => [self::SURPLUS],
        ]);
    }
}
