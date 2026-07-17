import { createRule } from './lib.js';

/** The member-carrying bodies whose first line is held clear of the brace. */
const BODY_TYPES = new Set(['TSInterfaceBody', 'ClassBody', 'TSEnumBody']);

/**
 * Require a blank line after the opening brace of an interface, class or enum
 * body.
 *
 * The first member then stands off from the declaration the way every later
 * member stands off from the one above it, so a body reads as an evenly spaced
 * list rather than crowding its opening line. Only the opening brace is
 * governed; the closing brace is left to sit against the final member.
 *
 * An empty body and a body written entirely on one line carry no member to
 * separate and are left alone. Where the first member opens with a
 * documentation comment the blank line falls above the comment, so the comment
 * stays attached to what it documents.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */
export default createRule({
    name: 'pad-block-start',
    meta: {
        type: 'layout',
        fixable: 'whitespace',
        docs: {
            description: 'Require a blank line after the opening brace of an interface, class or enum body.',
        },
        schema: [],
        messages: {
            missing: 'A body must begin with a blank line after its opening brace.',
        },
    },
    defaultOptions: [],
    create(context) {
        const { sourceCode } = context;

        /** Hold the first member of a body clear of the opening brace. */
        function inspect(node) {
            const brace = sourceCode.getFirstToken(node);
            const close = sourceCode.getLastToken(node);

            // A one-line body has no member to separate from the brace.
            if (!brace || !close || brace.loc.end.line === close.loc.start.line) {
                return;
            }

            const first = sourceCode.getTokenAfter(brace, { includeComments: true });

            // An empty body carries nothing to stand off from the brace.
            if (!first || first === close) {
                return;
            }

            const blanks = first.loc.start.line - brace.loc.end.line - 1;

            if (blanks === 1) {
                return;
            }

            const onOwnLine = first.loc.start.line > brace.loc.end.line;

            context.report({
                loc: brace.loc,
                messageId: 'missing',
                fix: onOwnLine
                    ? fixer => fixer.replaceTextRange(
                        [brace.range[1], first.range[0]],
                        `\n\n${' '.repeat(first.loc.start.column)}`,
                    )
                    : null,
            });
        }

        const visitor = {};

        for (const type of BODY_TYPES) {
            visitor[type] = inspect;
        }

        return visitor;
    },
});
