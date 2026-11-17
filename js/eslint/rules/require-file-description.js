import { createRule, fileDocBlock } from './lib.js';

const DEFAULT_TAGS = ['copyright', 'author'];

/** A line opening a documentation tag rather than carrying prose. */
const TAG_LINE = /^@[A-Za-z][\w-]*(?:\s|$)/;

/** The prose a documentation line carries, with its leading margin removed. */
function content(line) {
    return line.replace(/^\s*\*\s*/, '').trim();
}

/** Whether the block opens with prose rather than going straight to its tags. */
function described(comment) {
    for (const line of comment.value.split('\n')) {
        const text = content(line);

        if (text === '') {
            continue;
        }

        return !TAG_LINE.test(text);
    }

    return false;
}

/**
 * Require the file's docblock to open with a prose summary above its tags.
 *
 * The block is found by its tags, as `require-copyright` finds it, so the two
 * rules' `tags` options should match. A missing block is left to that rule.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */
export default createRule({
    name: 'require-file-description',
    meta: {
        type: 'suggestion',
        docs: {
            description: "Require the file's documentation comment to open with a description.",
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
            missing: 'The documentation comment carrying {{ tags }} must open with a description.',
        },
    },
    defaultOptions: [{ tags: DEFAULT_TAGS }],
    create(context, [options]) {
        const { sourceCode } = context;
        const required = options.tags ?? DEFAULT_TAGS;

        return {
            Program() {
                const doc = fileDocBlock(sourceCode, required);

                if (doc === null || described(doc)) {
                    return;
                }

                context.report({
                    node: doc,
                    messageId: 'missing',
                    data: { tags: required.map(tag => `@${tag}`).join(', ') },
                });
            },
        };
    },
});
