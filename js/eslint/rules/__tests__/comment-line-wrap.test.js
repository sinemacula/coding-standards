/**
 * Tests for the comment-line-wrap rule.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */

import rule from '../comment-line-wrap.js';
import { ruleTester } from './tester.js';

ruleTester.run('comment-line-wrap', rule, {
    valid: [
        // A short standalone comment well within the width.
        '// A short standalone comment well within the width.\nconst x = 1;',
        // A `//` run already wrapped as tightly as the width allows.
        '// A longer standalone comment that has already been wrapped greedily so that\n// every one of its lines is filled as far as the width will allow it to be.\nconst x = 1;',
        // A line whose overflow is a single URL is left unwrapped.
        '// See https://example.com/a/very/long/path/that/keeps/going/well/past/the/eighty/character/limit/ok\nconst x = 1;',
        // A single unbreakable token is left alone.
        '//ThisIsASingleVeryLongCommentTokenWithoutAnySpacesThatExceedsTheEightyCharacterLimitForSureXX\nconst x = 1;',
        // A directive line is never reflowed, whatever its length.
        '// prettier-ignore because the construct below is intentionally formatted by hand and must be left exactly so\nconst x = 1;',
        // A docblock description already wrapped greedily.
        '/**\n * A description already wrapped greedily so that each of its lines is filled\n * with as many whole words as the width will accept before the next one here.\n */\nexport const a = 1;',
        // A docblock tag line is exempt even when it overflows.
        '/**\n * @param foo - a parameter with a description that runs comfortably beyond the eighty character mark here\n */\nexport const a = 1;',
        // A compact single-line docblock beyond the width is exempt.
        '/** A single-line docblock that is intentionally written to run beyond the eighty character limit ok yes. */\nexport const a = 1;',
        // A trailing comment after code is not a standalone run.
        'const x = 1; // a trailing comment that pushes this code line beyond the eighty character limit for sure ok',
        // Fenced code, a table and a separator render verbatim.
        '/**\n * Intro that is short.\n *\n * ```\n * const example = codeInsideAFenceThatRunsWellBeyondTheEightyCharacterLimitForSure();\n * ```\n *\n * | a | b that runs well beyond the eighty character limit for a table row here yes |\n * =========================================================================================\n */\nexport const a = 1;',
        // A list already wrapped with its hanging indentation.
        '/**\n * - a list item already wrapped as tightly as it can be so that the rule will\n *   leave its hanging indentation exactly as the author has laid it out here\n * - a short item\n */\nexport const a = 1;',
        // A single-line non-doc block comment is left alone, however long.
        '/* a single-line block comment left alone even when it runs far beyond the eighty character width for sure ok now */\nconst a = 1;',
        // A multi-line non-doc block comment is never reflowed.
        '/*\n * A plain block comment (not a docblock) whose long prose line is left entirely alone even beyond the eighty char width here now.\n */\nconst a = 1;',
        // A framework configuration block (Laravel config style) is verbatim.
        '/*\n|--------------------------------------------------------------------------\n| Heading\n|--------------------------------------------------------------------------\n|\n| A framework-style configuration block whose lines are left entirely alone however far beyond eighty they run on.\n|\n*/\nconst a = 1;',
        // A malformed docblock (no leading star on a line) is left alone.
        '/**\n a malformed docblock whose interior line has no leading star so it is left untouched even when the line runs beyond eighty chars here\n */\nconst a = 1;',
        // A docblock with inconsistent star indentation is left alone.
        '/**\n * a docblock whose interior lines carry inconsistent indentation before the star\n  * so the whole block is left untouched however far beyond eighty the lines run on now\n */\nconst a = 1;',
        // An empty docblock has no prose to reflow.
        '/**\n *\n */\nconst a = 1;',
    ],
    invalid: [
        {
            // An overflowing `//` line wraps.
            code: '// This standalone prose comment is intentionally written to run far beyond the eighty character limit for sure.\nconst x = 1;',
            output: '// This standalone prose comment is intentionally written to run far beyond the\n// eighty character limit for sure.\nconst x = 1;',
            errors: [{ messageId: 'tooLong' }],
        },
        {
            // A prematurely wrapped `//` run merges greedily.
            code: '// This standalone comment run\n// was wrapped far too early\n// on each of its lines here.\nconst x = 1;',
            output: '// This standalone comment run was wrapped far too early on each of its lines\n// here.\nconst x = 1;',
            errors: [{ messageId: 'prematureWrap' }, { messageId: 'prematureWrap' }],
        },
        {
            // An overflowing docblock description wraps.
            code: '/**\n * This is a long descriptive prose paragraph inside the docblock that runs comfortably beyond eighty chars.\n */\nexport const a = 1;',
            output: '/**\n * This is a long descriptive prose paragraph inside the docblock that runs\n * comfortably beyond eighty chars.\n */\nexport const a = 1;',
            errors: [{ messageId: 'tooLong' }],
        },
        {
            // A long list item wraps, keeping its hanging indentation.
            code: '/**\n * - a list item that is quite long and will need to wrap onto a second line with a hanging indent applied\n */\nexport const a = 1;',
            output: '/**\n * - a list item that is quite long and will need to wrap onto a second line\n *   with a hanging indent applied\n */\nexport const a = 1;',
            errors: [{ messageId: 'tooLong' }],
        },
        {
            // Overflow and premature wrapping are reported separately.
            code: '/**\n * Tiny.\n * This second line of the same paragraph is intentionally written to overflow far past the eighty limit here.\n */\nexport const a = 1;',
            output: '/**\n * Tiny. This second line of the same paragraph is intentionally written to\n * overflow far past the eighty limit here.\n */\nexport const a = 1;',
            errors: [{ messageId: 'prematureWrap' }, { messageId: 'tooLong' }],
        },
        {
            // The configured maximum length drives the width.
            code: '// alpha beta gamma delta epsilon zeta eta theta iota kappa\nconst x = 1;',
            options: [{ maxLength: 40 }],
            output: '// alpha beta gamma delta epsilon zeta\n// eta theta iota kappa\nconst x = 1;',
            errors: [{ messageId: 'tooLong' }],
        },
        {
            // Adjacent `//` comments at different indents are separate runs; the
            // deeper one reflows within its own indent and the other is left be.
            code: '// Short comment at column one that fits within the width comfortably here now.\n    // A deeper indented standalone comment that runs on well beyond the eighty character limit for sure.\nconst value = 1;',
            output: '// Short comment at column one that fits within the width comfortably here now.\n    // A deeper indented standalone comment that runs on well beyond the eighty\n    // character limit for sure.\nconst value = 1;',
            errors: [{ messageId: 'tooLong' }],
        },
    ],
});
