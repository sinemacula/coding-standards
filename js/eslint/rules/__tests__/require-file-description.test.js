/**
 * Tests for the require-file-description rule.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */

import rule from '../require-file-description.js';
import { ruleTester } from './tester.js';

ruleTester.run('require-file-description', rule, {
    valid: [
        '/**\n * Summary line.\n *\n * @author Y\n * @copyright 2026 X\n */\nexport const a = 1;',
        '/** Summary line.\n * @author Y\n * @copyright 2026 X\n */\nexport const a = 1;',
        '/**\n * Summary line.\n *\n * A second paragraph.\n *\n * @author Y\n * @copyright 2026 X\n */\nexport const a = 1;',
        "import { z } from 'z';\n/**\n * A module.\n *\n * @author Y\n * @copyright 2026 X\n */\nexport const a = z;",
        '#!/usr/bin/env node\n/**\n * Summary line.\n *\n * @author Y\n * @copyright 2026 X\n */\nconst a = 1;',
        '/*\n * Summary line.\n *\n * @author Y\n * @copyright 2026 X\n */\nexport const a = 1;',
        // No header at all is require-copyright's to report.
        'export const a = 1;',
        '/**\n * @author Y\n */\nexport const a = 1;',
        // Only the block carrying every tag is the header.
        '/**\n * @author Y\n */\n/**\n * Summary line.\n *\n * @author Y\n * @copyright 2026 X\n */\nexport const a = 1;',
        '/**\n * Wraps mail sent to foo@example.com.\n *\n * @author Y\n * @copyright 2026 X\n */\nexport const a = 1;',
        {
            code: '/**\n * Summary line.\n *\n * @copyright 2026 X\n */\nexport const a = 1;',
            options: [{ tags: ['copyright'] }],
        },
    ],
    invalid: [
        {
            code: '/**\n * @author Y\n * @copyright 2026 X\n */\nexport const a = 1;',
            errors: [{ messageId: 'missing' }],
        },
        {
            code: '/**\n *\n * @author Y\n * @copyright 2026 X\n */\nexport const a = 1;',
            errors: [{ messageId: 'missing' }],
        },
        {
            code: '/** @author Y @copyright 2026 X */\nexport const a = 1;',
            errors: [{ messageId: 'missing' }],
        },
        {
            // Prose below the tags is not a summary.
            code: '/**\n * @author Y\n * @copyright 2026 X\n *\n * A trailing note.\n */\nexport const a = 1;',
            errors: [{ messageId: 'missing' }],
        },
        {
            // A wrapped tag value is not prose.
            code: '/**\n * @author Y\n * @copyright 2026 A Very Long Holder Name That\n *             wraps onto a second line\n */\nexport const a = 1;',
            errors: [{ messageId: 'missing' }],
        },
        {
            code: '/**\n * A note about the line below.\n */\n/**\n * @author Y\n * @copyright 2026 X\n */\nexport const a = 1;',
            errors: [{ messageId: 'missing' }],
        },
        {
            code: "import { z } from 'z';\n/**\n * @author Y\n * @copyright 2026 X\n */\nexport const a = z;",
            errors: [{ messageId: 'missing' }],
        },
        {
            code: '/**\n * @copyright 2026 X\n */\nexport const a = 1;',
            options: [{ tags: ['copyright'] }],
            errors: [{ messageId: 'missing' }],
        },
    ],
});
