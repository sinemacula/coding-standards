<?php

declare(strict_types = 1);

namespace SineMacula\Tests\Standard;

use PhpCsFixer\FixerFactory;
use PhpCsFixer\RuleSet\RuleSet;
use PhpCsFixer\Tokenizer\Tokens;
use PHPUnit\Framework\Attributes\CoversNothing;
use SineMacula\Tests\AbstractStandardTestCase;

/**
 * Tests that the formatter and the sniffs agree about the same file.
 *
 * Both run over a consumer's code, the formatter first, so a shape the
 * formatter writes and the sniffs then reject cannot be resolved by the
 * consumer at all: formatting reintroduces the violation every time. Reading
 * either ruleset alone cannot show that, since each is self-consistent. The
 * fixture is written the way the sniffs require, so anything the formatter
 * changes about it is a disagreement between the two.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 *
 * @internal
 *
 * @SuppressWarnings("php:S4833")
 * @SuppressWarnings("php:S2003")
 */
#[CoversNothing]
final class FormatterAgreementTest extends AbstractStandardTestCase
{
    /** @var string Where the formatted fixture is written for the sniffs to read. */
    private string $formatted = '';

    /**
     * Remove the formatted fixture.
     *
     * @return void
     */
    #[\Override]
    protected function tearDown(): void
    {
        if ($this->formatted === '' || file_exists($this->formatted) === false) {
            return;
        }

        unlink($this->formatted);
    }

    /**
     * The standard accepts its own fixture once the formatter has been over it.
     * Member doc comments are the case that has diverged: the formatter decides
     * the span of each kind separately, and a kind it is not told about takes
     * its own default, which for enum cases is the multi-line shape the sniffs
     * refuse.
     *
     * @return void
     */
    public function testTheSniffsAcceptTheFormattedEnum(): void
    {
        $this->assertStandardReportsFor($this->format('FormatterAgreementEnum.inc'), []);
    }

    /**
     * The same for the members of a class, so the spans the formatter is told
     * about stay covered alongside the one it was not.
     *
     * @return void
     */
    public function testTheSniffsAcceptTheFormattedClass(): void
    {
        $this->assertStandardReportsFor($this->format('FormatterAgreementClass.inc'), []);
    }

    /**
     * A tag left bare by the description sniff is a shape both sides accept:
     * the formatter leaves it exactly as the fixer wrote it and the sniffs
     * report nothing. A described tag has no such shape, since the formatter
     * lowercases the description and strips its full stop while the sniffs ask
     * for both.
     *
     * @return void
     */
    public function testTheFormatterKeepsTheBareTagsTheFixerWrites(): void
    {
        $fixed     = dirname(__DIR__) . '/Commenting/DisallowTagDescription.inc.fixed';
        $formatted = $this->format('../Commenting/DisallowTagDescription.inc.fixed');

        self::assertFileEquals($fixed, $formatted);

        $this->assertStandardReportsFor($formatted, []);
    }

    /**
     * An @param type written across lines is a shape both sides accept: the
     * formatter leaves it as written, and the sniffs follow the type to its
     * close to find the variable, where reading the tag's first line alone
     * finds none.
     *
     * @return void
     */
    public function testBothSidesAcceptAParamTypeAcrossLines(): void
    {
        $formatted = $this->format('FormatterAgreementParamTag.inc');

        self::assertFileEquals(__DIR__ . '/FormatterAgreementParamTag.inc', $formatted);

        $this->assertStandardReportsFor($formatted, []);
    }

    /**
     * An assignment a @var tag names is kept, not folded into the return that
     * reads it, since folding it leaves the tag naming a variable that no
     * longer exists, which PHPStan reports.
     *
     * @return void
     */
    public function testTheFormatterKeepsTheVariableAVarTagNames(): void
    {
        self::assertFileEquals(__DIR__ . '/FormatterAgreementVarTag.inc', $this->format('FormatterAgreementVarTag.inc'));
    }

    /**
     * Run the shipped formatter rules over a fixture and write the result where
     * the sniffs can read it, returning that path.
     *
     * @param  string  $fixture
     * @return string
     */
    private function format(string $fixture): string
    {
        $code   = (string) file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . $fixture);
        $rules  = require dirname(__DIR__, 3) . '/php/.php-cs-fixer.rules.php';
        $tokens = Tokens::fromCode($code);

        $this->formatted = sys_get_temp_dir() . '/' . basename($fixture) . '.formatted.php';

        $file = new \SplFileInfo($this->formatted);

        foreach ((new FixerFactory)->registerBuiltInFixers()->useRuleSet(new RuleSet($rules))->getFixers() as $fixer) {
            if ($fixer->isCandidate($tokens) === false || $fixer->supports($file) === false) {
                continue;
            }

            $fixer->fix($file, $tokens);
        }

        file_put_contents($this->formatted, $tokens->generateCode());

        return $this->formatted;
    }
}
