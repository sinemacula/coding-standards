import { createRule } from './lib.js';

const DEFAULT_TAGS = ['author', 'copyright'];
const DEFAULT_COLUMN = 14;

/** A documentation line opening a tag that carries a value. */
const TAG_LINE = /^(\s*\*\s*)@([A-Za-z][\w-]*)([^\S\n]+)(?=\S)/;

/**
 * The spaces that place a tag's value at the target column, or null when the
 * tag is too long to reach it.
 */
function padding(tag, column) {
    const spaces = column - tag.length - 2;

    return spaces > 0 ? ' '.repeat(spaces) : null;
}

/**
 * Align the values of the listed documentation tags (`@author` and `@copyright`
 * by default) at one column, counted from the `@`.
 *
 * A tag too long to reach the column is skipped. Presence of the tags is
 * enforced by `require-copyright`.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */
export default createRule({
    name: 'align-doc-tags',
    meta: {
        type: 'layout',
        fixable: 'whitespace',
        docs: {
            description: 'Align the values of the documentation tags a file header declares.',
        },
        schema: [
            {
                type: 'object',
                properties: {
                    tags: {
                        type: 'array',
                        items: { type: 'string' },
                    },
                    column: {
                        type: 'integer',
                        minimum: 1,
                    },
                },
                additionalProperties: false,
            },
        ],
        messages: {
            misaligned: 'The @{{ tag }} value must start at column {{ column }}.',
        },
    },
    defaultOptions: [{ tags: DEFAULT_TAGS, column: DEFAULT_COLUMN }],
    create(context, [options]) {
        const { sourceCode } = context;
        const tags = new Set((options.tags ?? DEFAULT_TAGS).map(tag => tag.toLowerCase()));
        const column = options.column ?? DEFAULT_COLUMN;

        /** Report and fix the spacing a single tag line carries. */
        function inspect(line, start) {
            const match = TAG_LINE.exec(line);

            if (!match) {
                return;
            }

            const [, prefix, tag, spacing] = match;

            if (!tags.has(tag.toLowerCase())) {
                return;
            }

            const desired = padding(tag, column);

            if (desired === null || spacing === desired) {
                return;
            }

            const from = start + prefix.length + 1 + tag.length;
            const to = from + spacing.length;

            context.report({
                loc: {
                    start: sourceCode.getLocFromIndex(from),
                    end: sourceCode.getLocFromIndex(to),
                },
                messageId: 'misaligned',
                data: { tag, column },
                fix: fixer => fixer.replaceTextRange([from, to], desired),
            });
        }

        return {
            Program() {
                for (const comment of sourceCode.getAllComments()) {
                    if (comment.type !== 'Block' || !comment.value.startsWith('*')) {
                        continue;
                    }

                    // Skip the opening `/*`.
                    let offset = comment.range[0] + 2;

                    for (const line of comment.value.split('\n')) {
                        inspect(line, offset);
                        offset += line.length + 1;
                    }
                }
            },
        };
    },
});
