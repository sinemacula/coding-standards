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
        // A class method already spanning lines.
        'class C {\n    /**\n     * Tick.\n     */\n    tick(): void {}\n}',
        // An interface method signature already spanning lines.
        'interface I {\n    /**\n     * Run.\n     */\n    run(): void;\n}',
        // A class field holding a function, already spanning lines.
        'class C {\n    /**\n     * Handle.\n     */\n    onTick = (): void => {};\n}',
        // A free function keeps the freedom of a single line.
        '/** Do it. */\nfunction go(): void {}',
        // A module arrow constant is likewise left to the author.
        '/** Do it. */\nconst go = (): void => {};',
        // A data property is not a method; its one-line comment is left be.
        'class C {\n    /** A count. */\n    count = 0;\n}',
        // A data property with a multi-line comment is still not this rule's
        // concern.
        'interface I {\n    /**\n     * A value.\n     */\n    a: number;\n}',
        // A method with no documentation comment is not governed here.
        'class C {\n    tick(): void {}\n}',
        // A leading line comment is not a documentation block.
        'class C {\n    // tick\n    tick(): void {}\n}',
        // An empty block carries no prose to unfold and is left to the
        // description rule.
        'class C {\n    /** */\n    tick(): void {}\n}',
    ],
    invalid: [
        {
            // A one-line class-method comment unfolds onto three lines.
            code: 'class C {\n    /** Tick. */\n    tick(): void {}\n}',
            output: 'class C {\n    /**\n     * Tick.\n     */\n    tick(): void {}\n}',
            errors: [{ messageId: 'singleLine' }],
        },
        {
            // A one-line interface method signature unfolds.
            code: 'interface I {\n    /** Run. */\n    run(): void;\n}',
            output: 'interface I {\n    /**\n     * Run.\n     */\n    run(): void;\n}',
            errors: [{ messageId: 'singleLine' }],
        },
        {
            // A one-line function field unfolds.
            code: 'class C {\n    /** Handle. */\n    onTick = (): void => {};\n}',
            output: 'class C {\n    /**\n     * Handle.\n     */\n    onTick = (): void => {};\n}',
            errors: [{ messageId: 'singleLine' }],
        },
        {
            // A one-line abstract method signature unfolds.
            code: 'abstract class C {\n    /** Tick. */\n    abstract tick(): void;\n}',
            output: 'abstract class C {\n    /**\n     * Tick.\n     */\n    abstract tick(): void;\n}',
            errors: [{ messageId: 'singleLine' }],
        },
        {
            // A tag-only one-line comment unfolds, the tag moving to its line.
            code: 'class C {\n    /** @override */\n    tick(): void {}\n}',
            output: 'class C {\n    /**\n     * @override\n     */\n    tick(): void {}\n}',
            errors: [{ messageId: 'singleLine' }],
        },
    ],
});
