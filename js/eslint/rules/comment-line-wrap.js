import { createRule } from './lib.js';
import { reflow } from './comment-reflow.js';

const DEFAULT_MAX_LENGTH = 80;

/** A docblock interior line: its indent, the star, and the prose after it. */
const DOC_LINE = /^([ \t\n\r\f\v]*)\*( ?)(.*)$/;

/** Whether only whitespace precedes the comment on its own line. */
function isStandalone(comment, sourceCode) {
    const line = sourceCode.lines[comment.loc.start.line - 1];

    return line.slice(0, comment.loc.start.column).trim() === '';
}

/**
 * Group standalone `//` comments into runs of adjacent lines sharing an indent,
 * so a wrapped paragraph is reflowed as one unit.
 */
function slashRuns(comments, sourceCode) {
    const runs = [];
    let current = null;

    for (const comment of comments) {
        if (comment.type !== 'Line' || !isStandalone(comment, sourceCode)) {
            current = null;
            continue;
        }

        const last = current?.[current.length - 1];

        if (current && comment.loc.start.line === last.loc.start.line + 1 && comment.loc.start.column === current[0].loc.start.column) {
            current.push(comment);
        } else {
            current = [comment];
            runs.push(current);
        }
    }

    return runs;
}

/** Strip the single optional space that follows a `//` from a comment's value. */
function slashContent(value) {
    return value.startsWith(' ') ? value.slice(1) : value;
}

/** Describe a `//` run: its content lines, margin, report locations and rebuild. */
function slashDescriptor(run, sourceCode, eol) {
    const first = run[0].loc.start;
    const indent = sourceCode.lines[first.line - 1].slice(0, first.column);

    return {
        content: run.map(comment => slashContent(comment.value)),
        marginWidth: indent.length + 3,
        locs: run.map(comment => comment.loc),
        range: [run[0].range[0], run[run.length - 1].range[1]],
        rebuild: lines => lines.map((line, offset) => `${offset === 0 ? '' : indent}//${line === '' ? '' : ` ${line}`}`).join(eol),
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
 * earlier than it needs to. Standalone `//` runs and multi-line docblocks are
 * governed; tag lines, suppression directives, fenced or indented code, tables,
 * separators and trailing comments after code are left untouched, as is a line
 * whose overflow is a single unbreakable token such as a long name or URL, and
 * a compact single-line docblock, which the single-line property rule governs.
 * The fix reflows each faulted paragraph to its greedy canonical form and is
 * idempotent.
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

                for (const run of slashRuns(comments, sourceCode)) {
                    enforce(context, slashDescriptor(run, sourceCode, eol), maxLength);
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
