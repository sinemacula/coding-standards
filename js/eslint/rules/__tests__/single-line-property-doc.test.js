/**
 * Tests for the single-line-property-doc rule.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */

import rule from '../single-line-property-doc.js';
import { ruleTester } from './tester.js';

ruleTester.run('single-line-property-doc', rule, {
    valid: [
        // An interface member already on one line.
        'interface I {\n    /** A value. */\n    a: number;\n}',
        // A class field already on one line.
        'class C {\n    /** A value. */\n    a = 1;\n}',
        // A property with no documentation comment is not this rule's concern.
        'interface I {\n    a: number;\n}',
        // A long description stays on one line, however far it runs.
        'interface I {\n    /** A description kept deliberately long to run well past any reasonable wrap column '
            + 'without being broken. */\n    a: number;\n}',
        // A class field holding a function may document across lines.
        'class C {\n    /**\n     * Handle activity.\n     *\n     * @param e - the event\n     */\n'
            + '    onActivity = (e: Event): void => {};\n}',
        // A method signature is not a data property.
        'interface I {\n    /**\n     * Search.\n     *\n     * @param term - the term\n     */\n'
            + '    search(term: string): void;\n}',
        // A leading line comment is not a documentation block.
        'interface I {\n    // a value\n    a: number;\n}',
        // An enum member documented on one line passes.
        'enum E {\n    /** The active state. */\n    ACTIVE,\n}',
        // An enum member is never required to carry a comment.
        'enum E {\n    ACTIVE,\n}',
    ],
    invalid: [
        {
            // A multi-line interface-member block folds to one line.
            code: 'interface I {\n    /**\n     * A value.\n     */\n    a: number;\n}',
            output: 'interface I {\n    /** A value. */\n    a: number;\n}',
            errors: [{ messageId: 'multiline' }],
        },
        {
            // A multi-line class-field block folds to one line.
            code: 'class C {\n    /**\n     * A value.\n     */\n    a = 1;\n}',
            output: 'class C {\n    /** A value. */\n    a = 1;\n}',
            errors: [{ messageId: 'multiline' }],
        },
        {
            // A wrapped description joins with single spaces.
            code: 'interface I {\n    /**\n     * A description that the author\n'
                + '     * split across two lines.\n     */\n    a: number;\n}',
            output: 'interface I {\n    /** A description that the author split across two lines. */\n'
                + '    a: number;\n}',
            errors: [{ messageId: 'multiline' }],
        },
        {
            // A function-typed data property is still data, so it folds.
            code: 'interface I {\n    /**\n     * A callback.\n     */\n    onDone: () => void;\n}',
            output: 'interface I {\n    /** A callback. */\n    onDone: () => void;\n}',
            errors: [{ messageId: 'multiline' }],
        },
        {
            // A block bearing a tag is reported but not folded.
            code: 'interface I {\n    /**\n     * A value.\n     *\n     * @deprecated use b\n     */\n'
                + '    a: number;\n}',
            output: null,
            errors: [{ messageId: 'multiline' }],
        },
        {
            // A multi-line enum-member block folds to one line.
            code: 'enum E {\n    /**\n     * The active state.\n     */\n    ACTIVE,\n}',
            output: 'enum E {\n    /** The active state. */\n    ACTIVE,\n}',
            errors: [{ messageId: 'multiline' }],
        },
    ],
});
