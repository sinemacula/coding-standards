/**
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */

import { createRule } from './lib.js';

const DEFAULT_TAGS = ['copyright', 'author'];

/** A boundary-anchored matcher for a documentation tag by its bare name. */
function tagMatcher(tag) {
    const escaped = tag.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');

    return new RegExp(`(?:^|[\\s*])@${escaped}(?![-\\w])`, 'i');
}

/**
 * The file-header block comments: the contiguous run of block comments sitting
 * before any code, past a leading shebang. The run ends at the first line
 * comment, the first token, or the end of the file, so a banner or pragma
 * block ahead of the documentation block still counts toward the header. A
 * leading line comment, or a block comment that follows code, yields an empty
 * run.
 */
function leadingHeader(sourceCode, firstToken) {
    const run = [];

    for (const comment of sourceCode.getAllComments()) {
        if (comment.type === 'Shebang' || comment.type === 'Hashbang') {
            continue;
        }

        if (firstToken && comment.range[0] > firstToken.range[0]) {
            break;
        }

        if (comment.type !== 'Block') {
            break;
        }

        run.push(comment);
    }

    return run;
}

/**
 * Require a file-header comment block carrying the documentation tags every
 * source file must declare, `@copyright` and `@author` by default.
 *
 * The header is the run of block comments at the very top of the file, before
 * the first statement; a leading shebang or blank lines may precede it, and a
 * banner or pragma block may sit ahead of the documentation block. A leading
 * line comment, or a block comment that follows any code, does not stand in
 * for a header. Only the presence of each required tag is checked, never its
 * value or alignment, which are matters of formatting. The required set is
 * configurable, so a project may drop `@author` or add tags of its own.
 */
export default createRule({
    name: 'require-copyright',
    meta: {
        type: 'suggestion',
        docs: {
            description: 'Require a file-header comment block carrying the copyright and author tags.',
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
            missingHeader: 'File must begin with a header comment block that includes {{ tags }}.',
            missingTag: 'File header comment must include an @{{ tag }} tag.',
        },
    },
    defaultOptions: [{ tags: DEFAULT_TAGS }],
    create(context, [options]) {
        const { sourceCode } = context;
        const required = options.tags ?? DEFAULT_TAGS;

        return {
            Program(node) {
                const firstToken = sourceCode.getFirstToken(node);
                const header = leadingHeader(sourceCode, firstToken);

                if (header.length === 0) {
                    context.report({
                        node,
                        loc: { line: 1, column: 0 },
                        messageId: 'missingHeader',
                        data: { tags: required.map(tag => `@${tag}`).join(', ') },
                    });

                    return;
                }

                const anchor = header[header.length - 1];

                for (const tag of required) {
                    const matcher = tagMatcher(tag);

                    if (!header.some(comment => matcher.test(comment.value))) {
                        context.report({
                            node,
                            loc: anchor.loc,
                            messageId: 'missingTag',
                            data: { tag },
                        });
                    }
                }
            },
        };
    },
});
