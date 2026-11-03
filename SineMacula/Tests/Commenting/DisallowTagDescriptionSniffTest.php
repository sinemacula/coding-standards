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
     * A description on an @param or @throws tag is flagged under its own code,
     * whether it shares the tag's line or starts on the next, and whatever the
     * parameter is: variadic, by reference or typed with a space. A bare tag is
     * not.
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
     * The fixer leaves each tag bare, dropping the continuation lines of its
     * description, and a second pass over the result changes nothing.
     *
     * @return void
     */
    public function testFixesToBareTags(): void
    {
        $this->assertFixes('DisallowTagDescription.inc');
    }

    /**
     * A type that spans lines or holds a variable of its own is read to the
     * parameter it types, an untyped parameter and a union are still found, and
     * a tag in any doc comment is governed. A tag with no variable or no type,
     * a bare tag closing a single-line comment, and every other tag, are left
     * alone.
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
        ]);
    }

    /**
     * The fixer keeps a type spread over lines whole, keeps a single-line
     * comment closed with a space, removes a description that runs past a blank
     * line, and keeps the comment closed when its description ended on the
     * closing line.
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
