/**
 * Tests for the multiline-function-doc rule.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */

import rule from '../multiline-function-doc.js';
import { ruleTester } from './tester.js';

ruleTester.run('multiline-function-doc', rule, {
    valid: [
        'class C {\n    /**\n     * Tick.\n     */\n    tick(): void {}\n}',
        'interface I {\n    /**\n     * Run.\n     */\n    run(): void;\n}',
        'class C {\n    /**\n     * Handle.\n     */\n    onTick = (): void => {};\n}',
        // Free functions and module arrows may stay on one line.
        '/** Do it. */\nfunction go(): void {}',
        '/** Do it. */\nconst go = (): void => {};',
        // Data properties are out of scope.
        'class C {\n    /** A count. */\n    count = 0;\n}',
        'interface I {\n    /**\n     * A value.\n     */\n    a: number;\n}',
        'class C {\n    tick(): void {}\n}',
        'class C {\n    // tick\n    tick(): void {}\n}',
        // An empty block is left to the description rule.
        'class C {\n    /** */\n    tick(): void {}\n}',
    ],
    invalid: [
        {
            code: 'class C {\n    /** Tick. */\n    tick(): void {}\n}',
            output: 'class C {\n    /**\n     * Tick.\n     */\n    tick(): void {}\n}',
            errors: [{ messageId: 'singleLine' }],
        },
        {
            code: 'interface I {\n    /** Run. */\n    run(): void;\n}',
            output: 'interface I {\n    /**\n     * Run.\n     */\n    run(): void;\n}',
            errors: [{ messageId: 'singleLine' }],
        },
        {
            code: 'class C {\n    /** Handle. */\n    onTick = (): void => {};\n}',
            output: 'class C {\n    /**\n     * Handle.\n     */\n    onTick = (): void => {};\n}',
            errors: [{ messageId: 'singleLine' }],
        },
        {
            code: 'abstract class C {\n    /** Tick. */\n    abstract tick(): void;\n}',
            output: 'abstract class C {\n    /**\n     * Tick.\n     */\n    abstract tick(): void;\n}',
            errors: [{ messageId: 'singleLine' }],
        },
        {
            code: 'class C {\n    /** @override */\n    tick(): void {}\n}',
            output: 'class C {\n    /**\n     * @override\n     */\n    tick(): void {}\n}',
            errors: [{ messageId: 'singleLine' }],
        },
    ],
});
