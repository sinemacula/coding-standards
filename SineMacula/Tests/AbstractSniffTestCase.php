<?php

declare(strict_types = 1);

namespace SineMacula\Tests;

use PHP_CodeSniffer\Config;
use PHP_CodeSniffer\Files\DummyFile;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Files\LocalFile;
use PHP_CodeSniffer\Ruleset;
use PHPUnit\Framework\TestCase;

/**
 * Base test case for Sine Macula PHP_CodeSniffer sniffs.
 *
 * The sniff under test is resolved from the "<SniffName>SniffTest" class name.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */
abstract class AbstractSniffTestCase extends TestCase
{
    /**
     * Assert that the sniff reports errors on exactly the given line numbers.
     *
     * @param  string  $fixture
     * @param  array<int, int>  $expectedLines
     * @return void
     */
    protected function assertErrorsOnLines(string $fixture, array $expectedLines): void
    {
        $actualLines = array_keys($this->process($fixture)->getErrors());

        sort($actualLines);
        sort($expectedLines);

        static::assertSame($expectedLines, $actualLines);
    }

    /**
     * Assert the short error codes reported on each line.
     *
     * @param  string  $fixture
     * @param  array<int, list<string>>  $expected
     * @return void
     */
    protected function assertErrorCodesOnLines(string $fixture, array $expected): void
    {
        $actual = [];

        foreach ($this->process($fixture)->getErrors() as $line => $columns) {
            foreach ($columns as $messages) {
                foreach ($messages as $message) {
                    $actual[$line][] = substr((string) strrchr($message['source'], '.'), 1);
                }
            }
        }

        ksort($actual);
        ksort($expected);

        static::assertSame($expected, $actual);
    }

    /**
     * Assert the rendered error messages reported on each line.
     *
     * @param  string  $fixture
     * @param  array<int, list<string>>  $expected
     * @return void
     */
    protected function assertErrorMessagesOnLines(string $fixture, array $expected): void
    {
        $actual = [];

        foreach ($this->process($fixture)->getErrors() as $line => $columns) {
            foreach ($columns as $messages) {
                foreach ($messages as $message) {
                    $actual[$line][] = $message['message'];
                }
            }
        }

        ksort($actual);
        ksort($expected);

        static::assertSame($expected, $actual);
    }

    /**
     * Assert that the sniff fixes the fixture to its `.fixed` companion,
     * idempotently.
     *
     * @param  string  $fixture
     * @return void
     */
    protected function assertFixes(string $fixture): void
    {
        $fixed = $this->fix($this->process($fixture));

        static::assertSame(
            file_get_contents($this->directory() . DIRECTORY_SEPARATOR . $fixture . '.fixed'),
            $fixed,
            'The fixer output does not match the expected .fixed file.',
        );

        static::assertSame($fixed, $this->fix($this->processContent($fixed)), 'The fixer is not idempotent.');
    }

    /**
     * Property overrides to apply to the sniff under test.
     *
     * @return array<string, mixed>
     */
    protected function sniffProperties(): array
    {
        return [];
    }

    /**
     * Run the sniff under test over a fixture in the test's own directory.
     *
     * @param  string  $fixture
     * @return \PHP_CodeSniffer\Files\LocalFile
     */
    private function process(string $fixture): LocalFile
    {
        $file = new LocalFile($this->directory() . DIRECTORY_SEPARATOR . $fixture, ...$this->boot());
        $file->process();

        return $file;
    }

    /**
     * Run the sniff under test over an in-memory string of code.
     *
     * @param  string  $content
     * @return \PHP_CodeSniffer\Files\DummyFile
     */
    private function processContent(string $content): DummyFile
    {
        $file = new DummyFile($content, ...$this->boot());
        $file->process();

        return $file;
    }

    /**
     * Run the fixer over an already-processed file and return its output.
     *
     * @param  \PHP_CodeSniffer\Files\File  $file
     * @return string
     */
    private function fix(File $file): string
    {
        $file->fixer->fixFile();

        return $file->fixer->getContents();
    }

    /**
     * Build the ruleset and config that run only the sniff under test.
     *
     * @return array{\PHP_CodeSniffer\Ruleset, \PHP_CodeSniffer\Config}
     */
    private function boot(): array
    {
        // An empty standard is rejected, so boot Generic and drop its sniffs.
        $config            = new Config(['--extensions=inc,php'], false);
        $config->standards = ['Generic'];

        $ruleset         = new Ruleset($config);
        $ruleset->sniffs = [];
        $ruleset->registerSniffs([$this->sniffFile()], [], []);
        $ruleset->populateTokenListeners();

        foreach ($ruleset->sniffs as $sniff) {
            foreach ($this->sniffProperties() as $property => $value) {
                $sniff->{$property} = $value;
            }
        }

        return [$ruleset, $config];
    }

    /**
     * Resolve the absolute path of the sniff under test from this test class.
     *
     * @return string
     */
    private function sniffFile(): string
    {
        return str_replace(
            [DIRECTORY_SEPARATOR . 'Tests' . DIRECTORY_SEPARATOR, 'SniffTest.php'],
            [DIRECTORY_SEPARATOR . 'Sniffs' . DIRECTORY_SEPARATOR, 'Sniff.php'],
            $this->testFile(),
        );
    }

    /**
     * The directory holding this test class and its fixtures.
     *
     * @return string
     */
    private function directory(): string
    {
        return dirname($this->testFile());
    }

    /**
     * Resolve the absolute path of the concrete test class.
     *
     * @return string
     *
     * @throws \LogicException
     */
    private function testFile(): string
    {
        $file = (new \ReflectionClass(static::class))->getFileName();

        if ($file === false) {
            throw new \LogicException(sprintf('Test class %s is not declared in a file.', static::class));
        }

        return $file;
    }
}
