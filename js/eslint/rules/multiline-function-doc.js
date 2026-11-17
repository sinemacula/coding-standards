import { createRule } from './lib.js';

/** Values that make a class field read as behaviour rather than data. */
const FUNCTION_VALUES = new Set(['ArrowFunctionExpression', 'FunctionExpression']);

/**
 * Whether the node declares a method or a method-like member: a class method,
 * an interface method signature, or a class field that holds a function.
 */
function isMethodMember(node) {
    switch (node.type) {
        case 'MethodDefinition':
        case 'TSAbstractMethodDefinition':
        case 'TSMethodSignature':
            return true;
        case 'PropertyDefinition':
            return Boolean(node.value) && FUNCTION_VALUES.has(node.value.type);
        default:
            return false;
    }
}

/** The single line of prose a one-line documentation block carries. */
function singleLineContent(value) {
    return value.replace(/^\*/, '').trim();
}

/**
 * Require a method's documentation comment to span multiple lines, the mirror
 * of the single-line form data properties take.
 *
 * Governs class methods, interface method signatures and function-valued class
 * fields; free functions are left to the author.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */
export default createRule({
    name: 'multiline-function-doc',
    meta: {
        type: 'layout',
        fixable: 'whitespace',
        docs: {
            description: 'Require a method documentation comment to span multiple lines.',
        },
        schema: [],
        messages: {
            singleLine: 'A method documentation comment must span multiple lines.',
        },
    },
    defaultOptions: [],
    create(context) {
        const { sourceCode } = context;

        /** Unfold a method's one-line documentation block onto three lines. */
        function inspect(node) {
            if (!isMethodMember(node)) {
                return;
            }

            const doc = sourceCode.getCommentsBefore(node).at(-1);

            if (!doc || doc.type !== 'Block' || !doc.value.startsWith('*')) {
                return;
            }

            if (doc.loc.start.line !== doc.loc.end.line) {
                return;
            }

            const content = singleLineContent(doc.value);

            // An empty block is the description rule's to report.
            if (content.length === 0) {
                return;
            }

            const pad = ' '.repeat(doc.loc.start.column);

            context.report({
                node: doc,
                messageId: 'singleLine',
                fix: fixer => fixer.replaceText(doc, `/**\n${pad} * ${content}\n${pad} */`),
            });
        }

        return {
            MethodDefinition: inspect,
            TSAbstractMethodDefinition: inspect,
            TSMethodSignature: inspect,
            PropertyDefinition: inspect,
        };
    },
});
