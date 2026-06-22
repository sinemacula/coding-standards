<?php

declare(strict_types = 1);

namespace SineMacula\CodingStandards\Sniffs\Concerns;

use PHP_CodeSniffer\Files\File;

/**
 * Detects PHPUnit test classes.
 *
 * A class is treated as a test when its name ends in `Test` or it extends a
 * class whose name ends in `TestCase`. Size metrics that are a smell in
 * production code (long methods, many methods) are legitimate in tests, so a
 * sniff can use this to exempt them.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */
trait DetectsTestClasses
{
    /**
     * Whether the class declared at the pointer is a test class.
     *
     * @param  \PHP_CodeSniffer\Files\File  $phpcsFile
     * @param  int  $classPtr
     * @return bool
     */
    protected function isTestClass(File $phpcsFile, int $classPtr): bool
    {
        if (str_ends_with((string) $phpcsFile->getDeclarationName($classPtr), 'Test')) {
            return true;
        }

        $parent = $phpcsFile->findExtendedClassName($classPtr);

        return $parent !== false && str_ends_with($parent, 'TestCase');
    }
}
