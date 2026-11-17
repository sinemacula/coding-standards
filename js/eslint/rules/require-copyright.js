import { createRule, fileDocBlock } from './lib.js';

const DEFAULT_TAGS = ['copyright', 'author'];

/**
 * Require one documentation comment carrying every listed tag (`@copyright` and
 * `@author` by default). Only presence is checked, not value or alignment.
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
