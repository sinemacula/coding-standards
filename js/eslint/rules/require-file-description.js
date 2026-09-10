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
 * Require the file's descriptive docblock to carry a summary, not tags alone.
 *
 * The house convention is a prose summary followed by the tags every file
 * declares, so `@author` and `@copyright` annotate a description rather than
 * standing as a header of their own. `require-copyright` has always asked for
 * the tags; this asks for the sentence they were meant to sit beneath, which
 * was the intent all along and the half nothing enforced. A file whose entire
 * header is two tags says who owns it and nothing about what it is.
 *
 * The block is located exactly as `require-copyright` locates it, by the tags
 * it carries, so the two rules always speak about the same comment; a project
 * changing one rule's `tags` should change the other's to match. A file with no
 * such block is left alone, since `require-copyright` already owns the block's
 * existence and faulting one omission twice would only double the report.
 *
 * Only the block's first non-blank line is read, and only whether it is prose.
 * That places the summary above the tags, as the convention writes it, and
 * keeps a wrapped tag value's continuation line from passing as a description.
 * What the sentence says is the author's business: the rule asserts that prose
 * is there, never that it is good.
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
