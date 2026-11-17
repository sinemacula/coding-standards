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
        'interface I {\n    /** A value. */\n    a: number;\n}',
        'class C {\n    /** A value. */\n    a = 1;\n}',
        'interface I {\n    a: number;\n}',
        'interface I {\n    /** A description kept deliberately long to run well past any reasonable wrap column '
            + 'without being broken. */\n    a: number;\n}',
        // Function fields and method signatures are not data properties.
        'class C {\n    /**\n     * Handle activity.\n     *\n     * @param e - the event\n     */\n'
            + '    onActivity = (e: Event): void => {};\n}',
        'interface I {\n    /**\n     * Search.\n     *\n     * @param term - the term\n     */\n'
            + '    search(term: string): void;\n}',
        'interface I {\n    // a value\n    a: number;\n}',
        'enum E {\n    /** The active state. */\n    ACTIVE,\n}',
        'enum E {\n    ACTIVE,\n}',
    ],
    invalid: [
        {
            code: 'interface I {\n    /**\n     * A value.\n     */\n    a: number;\n}',
            output: 'interface I {\n    /** A value. */\n    a: number;\n}',
            errors: [{ messageId: 'multiline' }],
        },
        {
            code: 'class C {\n    /**\n     * A value.\n     */\n    a = 1;\n}',
            output: 'class C {\n    /** A value. */\n    a = 1;\n}',
            errors: [{ messageId: 'multiline' }],
        },
        {
            code: 'interface I {\n    /**\n     * A description that the author\n'
                + '     * split across two lines.\n     */\n    a: number;\n}',
            output: 'interface I {\n    /** A description that the author split across two lines. */\n'
                + '    a: number;\n}',
            errors: [{ messageId: 'multiline' }],
        },
        {
            // A function-typed property is still data.
            code: 'interface I {\n    /**\n     * A callback.\n     */\n    onDone: () => void;\n}',
            output: 'interface I {\n    /** A callback. */\n    onDone: () => void;\n}',
            errors: [{ messageId: 'multiline' }],
        },
        {
            code: 'interface I {\n    /**\n     * A value.\n     *\n     * @deprecated use b\n     */\n'
                + '    a: number;\n}',
            output: null,
            errors: [{ messageId: 'multiline' }],
        },
        {
            code: 'enum E {\n    /**\n     * The active state.\n     */\n    ACTIVE,\n}',
            output: 'enum E {\n    /** The active state. */\n    ACTIVE,\n}',
            errors: [{ messageId: 'multiline' }],
        },
    ],
});
