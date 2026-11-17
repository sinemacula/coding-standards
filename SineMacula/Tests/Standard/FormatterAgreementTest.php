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
 * The formatter runs first, so the sniffs must accept whatever it writes.
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
     * The sniffs accept the enum fixture once formatted.
     *
     * @return void
     */
    public function testTheSniffsAcceptTheFormattedEnum(): void
    {
        $this->assertStandardReportsFor($this->format('FormatterAgreementEnum.inc'), []);
    }

    /**
     * The sniffs accept the class fixture once formatted.
     *
     * @return void
     */
    public function testTheSniffsAcceptTheFormattedClass(): void
    {
        $this->assertStandardReportsFor($this->format('FormatterAgreementClass.inc'), []);
    }

    /**
     * The formatter keeps the bare tags the fixer writes, and the sniffs pass.
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
     * Both sides accept an @param type written across lines.
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
     * The formatter keeps an assignment that a @var tag names.
     *
     * @return void
     */
    public function testTheFormatterKeepsTheVariableAVarTagNames(): void
    {
        self::assertFileEquals(__DIR__ . '/FormatterAgreementVarTag.inc', $this->format('FormatterAgreementVarTag.inc'));
    }

    /**
     * Format a fixture with the shipped rules and return the output path.
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
