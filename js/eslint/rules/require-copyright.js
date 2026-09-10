import { createRule, fileDocBlock } from './lib.js';

const DEFAULT_TAGS = ['copyright', 'author'];

/**
 * Require a documentation comment carrying the tags every source file must
 * declare, `@copyright` and `@author` by default.
 *
 * A single block comment must carry all of the required tags together, so they
 * sit inside the file's descriptive docblock alongside its summary rather than
 * in a separate header. Only the presence of each tag is checked, never its
 * value or alignment, which are matters of formatting. The required set is
 * configurable, so a project may drop `@author` or add tags of its own. The
 * summary those tags sit beside is the separate concern of
 * `require-file-description`, which locates the same block by the same tags.
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

        return {
            Program(node) {
                if (fileDocBlock(sourceCode, required) !== null) {
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
