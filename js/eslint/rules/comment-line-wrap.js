import { createRule } from './lib.js';
import { reflow } from './comment-reflow.js';

const DEFAULT_MAX_LENGTH = 80;

/** A docblock interior line: its indent, the star, and the prose after it. */
const DOC_LINE = /^([ \t\n\r\f\v]*)\*( ?)(.*)$/;

/** The comment types a parser gives a line or block comment it recognises. */
const COMMENT_TYPES = ['Line', 'Block'];

/** The line-comment openers governed, matched longest first. */
const LINE_TOKENS = ['//', '#'];

/** Whether only whitespace precedes the comment on its own line. */
function isStandalone(comment, sourceCode) {
    const line = sourceCode.lines[comment.loc.start.line - 1];

    return line.slice(0, comment.loc.start.column).trim() === '';
}

/**
 * The line-comment token a comment opens with, or null when it opens none.
 *
 * Read from the source text rather than inferred from the comment's type,
 * because parsers label the same shape differently: a `//` comment arrives as
 * `Line`, while yaml-eslint-parser tags a `#` comment `Block`. Requiring one of
 * the two recognised types first leaves out a hashbang, which is neither and
 * whose leading `#` must never be read as prose.
 */
function lineToken(comment, sourceCode) {
    if (!COMMENT_TYPES.includes(comment.type)) {
        return null;
    }

    const opener = sourceCode.text.slice(comment.range[0], comment.range[0] + 2);

    return LINE_TOKENS.find(token => opener.startsWith(token)) ?? null;
}

/**
 * Group standalone line comments into runs of adjacent lines sharing a token
 * and an indent, so a wrapped paragraph is reflowed as one unit.
 */
function lineRuns(comments, sourceCode) {
    const runs = [];
    let current = null;

    for (const comment of comments) {
        const token = lineToken(comment, sourceCode);

        if (token === null || !isStandalone(comment, sourceCode)) {
            current = null;
            continue;
        }

        if (current !== null && continues(current, comment, token)) {
            current.comments.push(comment);
        } else {
            current = { token, comments: [comment] };
            runs.push(current);
        }
    }

    return runs;
}

/** Whether a comment extends the open run: same token, next line, same column. */
function continues(run, comment, token) {
    const last = run.comments[run.comments.length - 1];

    return run.token === token
        && comment.loc.start.line === last.loc.start.line + 1
        && comment.loc.start.column === run.comments[0].loc.start.column;
}

/** Strip the single optional space that follows the token from a value. */
function lineContent(value) {
    return value.startsWith(' ') ? value.slice(1) : value;
}

/** Describe a line-comment run: content, margin, report locations and rebuild. */
function lineDescriptor(run, sourceCode, eol) {
    const { token, comments } = run;
    const first = comments[0].loc.start;
    const indent = sourceCode.lines[first.line - 1].slice(0, first.column);

    return {
        content: comments.map(comment => lineContent(comment.value)),
        marginWidth: indent.length + token.length + 1,
        locs: comments.map(comment => comment.loc),
        range: [comments[0].range[0], comments[comments.length - 1].range[1]],
        rebuild: lines => lines.map((line, offset) => `${offset === 0 ? '' : indent}${token}${line === '' ? '' : ` ${line}`}`).join(eol),
    };
}

/** The margin-stripped interior lines of a docblock, or null for an odd shape. */
function docContent(comment, sourceCode) {
    const { lines } = sourceCode;
    const open = comment.loc.start.line;
    const close = comment.loc.end.line;

    if (open === close || lines[open - 1].trim() !== '/**' || lines[close - 1].trim() !== '*/') {
        return null;
    }

    const content = [];
    const locs = [];
    let indent = null;

    for (let line = open + 1; line < close; line += 1) {
        const match = DOC_LINE.exec(lines[line - 1]);

        if (!match || (indent !== null && match[1] !== indent)) {
            return null;
        }

        indent ??= match[1];
        content.push(match[3]);
        locs.push({ start: { line, column: 0 }, end: { line, column: lines[line - 1].length } });
    }

    return content.length === 0 ? null : { content, locs, indent };
}

/** Describe a docblock: its content lines, margin, report locations and rebuild. */
function docDescriptor(comment, sourceCode, eol) {
    if (comment.type !== 'Block' || !comment.value.startsWith('*')) {
        return null;
    }

    const parsed = docContent(comment, sourceCode);

    if (parsed === null) {
        return null;
    }

    const { content, locs, indent } = parsed;

    return {
        content,
        marginWidth: indent.length + 2,
        locs,
        range: comment.range,
        rebuild: lines => `/**${eol}${lines.map(line => (line === '' ? `${indent}*` : `${indent}* ${line}`)).join(eol)}${eol}${indent}*/`,
    };
}

/** Reflow a block and report each overflowing and prematurely wrapped line. */
function enforce(context, descriptor, maxLength) {
    const result = reflow(descriptor.content, descriptor.marginWidth, maxLength);

    if (result.long.length === 0 && result.premature.length === 0) {
        return;
    }

    /** Replace the whole comment block with its reflowed, canonical form. */
    const fix = fixer => fixer.replaceTextRange(descriptor.range, descriptor.rebuild(result.lines));

    /** Report one faulted line under the given message. */
    const report = (index, messageId) => context.report({ loc: descriptor.locs[index], messageId, data: { max: maxLength }, fix });

    result.long.forEach(index => report(index, 'tooLong'));
    result.premature.forEach(index => report(index, 'prematureWrap'));
}

/**
 * Keep standalone comment prose wrapped to a readable width.
 *
 * The syntax-only counterpart of the PHP comment line length sniff. It fills
 * each line greedily with as many whole words as fit and reports two faults on
 * their own footings: a line that overflows the width, and a line that wraps
 * earlier than it needs to. Standalone runs of the line-comment tokens `//` and
 * `#` and multi-line docblocks are governed; tag lines, suppression directives,
 * fenced or indented code, tables, separators and trailing comments after code
 * are left untouched, as is a line whose overflow is a single unbreakable token
 * such as a long name or URL, and a compact single-line docblock, which the
 * single-line property rule governs. The fix reflows each faulted paragraph to
 * its greedy canonical form and is idempotent.
 *
 * The `#` token carries the rule into YAML through yaml-eslint-parser, where it
 * reaches only standalone comments: block-scalar bodies are content rather than
 * comments, so a shell comment inside a `run:` step is never seen, and a
 * comment trailing a value is not standalone. Each token reclaims its own
 * width, so a `#` comment fills one column further than a `//` one.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */
export default createRule({
    name: 'comment-line-wrap',
    meta: {
        type: 'layout',
        fixable: 'whitespace',
        docs: {
            description: 'Keep standalone comment prose wrapped greedily to a readable width.',
        },
        schema: [
            {
                type: 'object',
                properties: {
                    maxLength: {
                        type: 'integer',
                        minimum: 1,
                    },
                },
                additionalProperties: false,
            },
        ],
        messages: {
            tooLong: 'Comment line must not exceed {{ max }} characters.',
            prematureWrap: 'Comment line wraps before it needs to; the next word fits within {{ max }} characters.',
        },
    },
    defaultOptions: [{ maxLength: DEFAULT_MAX_LENGTH }],
    create(context, [options]) {
        const { sourceCode } = context;
        const maxLength = options.maxLength ?? DEFAULT_MAX_LENGTH;
        const eol = sourceCode.text.includes('\r\n') ? '\r\n' : '\n';

        return {
            Program() {
                const comments = sourceCode.getAllComments();

                for (const run of lineRuns(comments, sourceCode)) {
                    enforce(context, lineDescriptor(run, sourceCode, eol), maxLength);
                }

                for (const comment of comments) {
                    const descriptor = docDescriptor(comment, sourceCode, eol);

                    if (descriptor !== null) {
                        enforce(context, descriptor, maxLength);
                    }
                }
            },
        };
    },
});
