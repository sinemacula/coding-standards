<?php

declare(strict_types = 1);

namespace SineMacula\CodingStandards\Sniffs\Concerns;

use PHP_CodeSniffer\Files\File;

/**
 * Detects test classes, so production-only rules can exempt test code.
 *
 * A test class is named `*Test`, extends `*TestCase`, or lives under `tests/`.
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

        if (str_contains(str_replace('\\', '/', $phpcsFile->getFilename()), '/tests/')) {
            return true;
        }

        $parent = $phpcsFile->findExtendedClassName($classPtr);

        return $parent !== false && str_ends_with($parent, 'TestCase');
    }
}
