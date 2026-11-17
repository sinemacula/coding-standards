/**
 * Tests for the require-copyright rule.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */

import rule from '../require-copyright.js';
import { ruleTester } from './tester.js';

ruleTester.run('require-copyright', rule, {
    valid: [
        '/**\n * @copyright 2026 X\n * @author Y\n */\nexport const a = 1;',
        // The docblock may follow the imports.
        "import { z } from 'z';\n/**\n * A module.\n *\n * @copyright 2026 X\n * @author Y\n */\nexport const a = z;",
        '/**\n * Summary line.\n *\n * @author Y\n * @copyright 2026 X\n */\nexport const a = 1;',
        '/**\n * @author Y\n * @license MIT\n * @copyright 2026 X\n */\nexport const a = 1;',
        '#!/usr/bin/env node\n/**\n * @copyright 2026 X\n * @author Y\n */\nconst a = 1;',
        '/**\n * @Copyright 2026 X\n * @AUTHOR Y\n */\nexport const a = 1;',
        { code: '/**\n * @copyright 2026 X\n */\nexport const a = 1;', options: [{ tags: ['copyright'] }] },
    ],
    invalid: [
        {
            code: 'export const a = 1;',
            errors: [{ messageId: 'missing' }],
        },
        {
            // Line comments do not count.
            code: '// @copyright 2026 X\n// @author Y\nexport const a = 1;',
            errors: [{ messageId: 'missing' }],
        },
        {
            code: '/**\n * @copyright 2026 X\n */\nexport const a = 1;',
            errors: [{ messageId: 'missing' }],
        },
        {
            code: '/**\n * @author Y\n */\nexport const a = 1;',
            errors: [{ messageId: 'missing' }],
        },
        {
            // One block must carry both tags.
            code: '/**\n * @copyright 2026 X\n */\n/**\n * @author Y\n */\nexport const a = 1;',
            errors: [{ messageId: 'missing' }],
        },
        {
            code: '/**\n * @copyrighted 2026 X\n * @author Y\n */\nexport const a = 1;',
            errors: [{ messageId: 'missing' }],
        },
        {
            code: '/**\n * @copyright 2026 X\n * @author Y\n */\nexport const a = 1;',
            options: [{ tags: ['copyright', 'author', 'license'] }],
            errors: [{ messageId: 'missing' }],
        },
    ],
});
