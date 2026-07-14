import { createRule } from './lib.js';

const DEFAULT_TAGS = ['copyright', 'author'];

/** A boundary-anchored matcher for a documentation tag by its bare name. */
function tagMatcher(tag) {
    const escaped = tag.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');

    return new RegExp(`(?:^|[\\s*])@${escaped}(?![-\\w])`, 'i');
}

/**
 * Require a documentation comment carrying the tags every source file must
 * declare, `@copyright` and `@author` by default.
 *
 * A single block comment must carry all of the required tags together, so they
 * sit inside the file's descriptive docblock alongside its summary rather than
 * in a separate header. Only the presence of each tag is checked, never its
 * value or alignment, which are matters of formatting. The required set is
 * configurable, so a project may drop `@author` or add tags of its own.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */
export default createRule({
    name: 'require-copyright',
    meta: {
        type: 'suggestion',
        docs: {
            description: 'Require a documentation comment carrying the copyright and author tags.',
        },
        schema: [
            {
                type: 'object',
                properties: {
                    tags: {
                        type: 'array',
                        items: { type: 'string' },
                    },
                },
                additionalProperties: false,
            },
        ],
        messages: {
            missing: 'A documentation comment must carry the {{ tags }} tags.',
        },
    },
    defaultOptions: [{ tags: DEFAULT_TAGS }],
    create(context, [options]) {
        const { sourceCode } = context;
        const required = options.tags ?? DEFAULT_TAGS;
        const matchers = required.map(tagMatcher);

        return {
            Program(node) {
                const documented = sourceCode.getAllComments().some(
                    comment => comment.type === 'Block' && matchers.every(matcher => matcher.test(comment.value)),
                );

                if (documented) {
                    return;
                }

                context.report({
                    node,
                    loc: { line: 1, column: 0 },
                    messageId: 'missing',
                    data: { tags: required.map(tag => `@${tag}`).join(', ') },
                });
            },
        };
    },
});
