<?php

declare(strict_types = 1);

namespace SineMacula\Tests\Commenting;

use PHPUnit\Framework\Attributes\CoversClass;
use SineMacula\Sniffs\Commenting\DisallowTagDescriptionSniff;
use SineMacula\Tests\AbstractSniffTestCase;

/**
 * Tests for the tag description sniff.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 *
 * @internal
 */
#[CoversClass(DisallowTagDescriptionSniff::class)]
final class DisallowTagDescriptionSniffTest extends AbstractSniffTestCase
{
    /** @var string The message rendered for a described @param tag. */
    private const string PARAM_MESSAGE = 'An @param tag must not carry a description; leave the tag bare, as in @param  int  $count.';

    /** @var string The message rendered for a described @throws tag. */
    private const string THROWS_MESSAGE = 'An @throws tag must not carry a description; leave the tag bare, as in @throws \RuntimeException.';

    /**
     * A description on an @param or @throws tag is flagged under its own code.
     *
     * @return void
     */
    public function testFlagsDescribedParamAndThrowsTags(): void
    {
        $this->assertErrorCodesOnLines('DisallowTagDescription.inc', [
            21 => ['ParamFound'],
            22 => ['ParamFound'],
            26 => ['ThrowsFound'],
            40 => ['ParamFound'],
            51 => ['ParamFound'],
            62 => ['ParamFound'],
            67 => ['ThrowsFound'],
        ]);
    }

    /**
     * The fixer strips each description, including its continuation lines.
     *
     * @return void
     */
    public function testFixesToBareTags(): void
    {
        $this->assertFixes('DisallowTagDescription.inc');
    }

    /**
     * The description is found past multi-line, quoted and union types.
     *
     * @return void
     */
    public function testFindsTheDescriptionPastAnyType(): void
    {
        $this->assertErrorCodesOnLines('DisallowTagDescriptionEdges.inc', [
            8  => ['ParamFound'],
            12 => ['ParamFound'],
            13 => ['ParamFound'],
            14 => ['ParamFound'],
            19 => ['ThrowsFound'],
            20 => ['ThrowsFound'],
            29 => ['ParamFound'],
            33 => ['ThrowsFound'],
            40 => ['ParamFound'],
            52 => ['ThrowsFound'],
            65 => ['ParamFound'],
            66 => ['ParamFound'],
            69 => ['ThrowsFound'],
            77 => ['ParamFound'],
        ]);
    }

    /**
     * The fixer keeps multi-line types whole and the comment closed.
     *
     * @return void
     */
    public function testFixesTheEdgesToBareTags(): void
    {
        $this->assertFixes('DisallowTagDescriptionEdges.inc');
    }

    /**
     * Each message names the tag and shows the bare shape it should take.
     *
     * @return void
     */
    public function testShowsTheBareShapeInTheMessage(): void
    {
        $this->assertErrorMessagesOnLines('DisallowTagDescription.inc', [
            21 => [self::PARAM_MESSAGE],
            22 => [self::PARAM_MESSAGE],
            26 => [self::THROWS_MESSAGE],
            40 => [self::PARAM_MESSAGE],
            51 => [self::PARAM_MESSAGE],
            62 => [self::PARAM_MESSAGE],
            67 => [self::THROWS_MESSAGE],
        ]);
    }
}
