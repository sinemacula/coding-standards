<?php

declare(strict_types = 1);

namespace SineMacula\CodingStandards\Sniffs\Concerns;

use PHP_CodeSniffer\Files\File;

/**
 * Detects test classes.
 *
 * A class is treated as a test when its name ends in `Test`, it extends a class
 * whose name ends in `TestCase`, or its file lives under a `tests/` directory.
 * Rules that only apply to production code (size metrics, the readonly mandate)
 * can use this to exempt test fixtures - recording spies, fakes and the like.
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
