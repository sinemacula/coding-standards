import { createRule } from './lib.js';

/** Class-field values that read as behaviour rather than data. */
const FUNCTION_VALUES = new Set(['ArrowFunctionExpression', 'FunctionExpression']);

/**
 * Whether the node is a data member: an interface property signature, an enum
 * member, or a class field that does not hold a function.
 */
function isDataProperty(node) {
    if (node.type === 'TSPropertySignature' || node.type === 'TSEnumMember') {
        return true;
    }

    return node.type === 'PropertyDefinition'
        && !(node.value && FUNCTION_VALUES.has(node.value.type));
}

/**
 * The text lines a documentation block carries, each stripped of its leading
 * margin and asterisk, with blank lines dropped.
 */
function contentLines(value) {
    return value
        .split('\n')
        .map(line => line.replace(/^\s*\*?[^\S\n]?/, '').trimEnd())
        .filter(line => line.length > 0);
}

/**
 * Require a data member's documentation comment (interface property, enum
 * member, non-function class field) to sit on a single line.
 *
 * A block carrying a tag such as `@deprecated` is reported without a fix, since
 * folding the tag into running prose would change its meaning.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */
export default createRule({
    name: 'single-line-property-doc',
    meta: {
        type: 'layout',
        fixable: 'whitespace',
        docs: {
            description: 'Require a data member documentation comment to sit on a single line.',
        },
        schema: [],
        messages: {
            multiline: 'A data member documentation comment must sit on a single line.',
        },
    },
    defaultOptions: [],
    create(context) {
        const { sourceCode } = context;

        /** Fold a data property's leading documentation block onto one line. */
        function inspect(node) {
            if (!isDataProperty(node)) {
                return;
            }

            const comments = sourceCode.getCommentsBefore(node);
            const doc = comments.at(-1);

            if (!doc || doc.type !== 'Block' || !doc.value.startsWith('*')) {
                return;
            }

            if (doc.loc.start.line === doc.loc.end.line) {
                return;
            }

            const lines = contentLines(doc.value);

            // An empty block is the description rule's to report.
            if (lines.length === 0) {
                return;
            }

            const hasTag = lines.some(line => line.startsWith('@'));

            context.report({
                node: doc,
                messageId: 'multiline',
                fix: hasTag ? null : fixer => fixer.replaceText(doc, `/** ${lines.join(' ')} */`),
            });
        }

        return {
            TSPropertySignature: inspect,
            TSEnumMember: inspect,
            PropertyDefinition: inspect,
        };
    },
});
