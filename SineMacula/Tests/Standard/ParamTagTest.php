<?php

declare(strict_types = 1);

namespace SineMacula\Tests\Standard;

use PHPUnit\Framework\Attributes\CoversNothing;
use SineMacula\Tests\AbstractStandardTestCase;

/**
 * Tests that the standard still holds @param tags to the signature once a tag's
 * type may span lines.
 *
 * The core function comment sniff reads a tag from its first line, so its
 * checks of the tag's variable are replaced by a sniff that reads the tag to
 * the close of its type. A parameter with no tag, a tag naming another variable
 * and a tag naming none are each still reported, whether the type sits on one
 * line or spans several.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 *
 * @internal
 */
#[CoversNothing]
final class ParamTagTest extends AbstractStandardTestCase
{
    /** @var string The code reported for a parameter with no tag. */
    private const string MISSING = 'SineMacula.Commenting.RequireParamTag.MissingParamTag';

    /** @var string The code reported for a traversable parameter with no tag. */
    private const string UNSPECIFIED = 'SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification';

    /**
     * A parameter left without a tag is reported beside a tag of either kind.
     *
     * @return void
     */
    public function testStillReportsAParameterWithNoTag(): void
    {
        $this->assertStandardReports('ParamTagMissing.inc', [
            15 => [self::MISSING],
            26 => [self::MISSING],
        ]);
    }

    /**
     * A tag naming a variable the signature does not declare is reported, and
     * so is the parameter it leaves undocumented.
     *
     * @return void
     */
    public function testStillReportsAMisnamedTag(): void
    {
        $this->assertStandardReports('ParamTagMisnamed.inc', [
            15 => [self::MISSING],
            18 => ['SineMacula.Commenting.RequireParamTag.ParamNameNoMatch'],
            26 => [self::MISSING],
            29 => ['SineMacula.Commenting.RequireParamTag.ParamNameNoMatch'],
            34 => [self::UNSPECIFIED],
        ]);
    }

    /**
     * A tag naming no variable is reported, and so is the parameter it leaves
     * undocumented.
     *
     * @return void
     */
    public function testStillReportsATagWithNoVariable(): void
    {
        $this->assertStandardReports('ParamTagUnnamed.inc', [
            15 => [self::MISSING],
            18 => ['SineMacula.Commenting.RequireParamTag.MissingParamName'],
            26 => [self::MISSING],
            29 => ['SineMacula.Commenting.RequireParamTag.MissingParamName'],
            34 => [self::UNSPECIFIED],
        ]);
    }
}
