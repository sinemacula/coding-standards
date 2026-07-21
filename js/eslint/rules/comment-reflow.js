/**
 * Deterministic greedy re-wrapper for comment prose.
 *
 * The syntax-only counterpart of the PHP comment line length sniff: given the
 * content lines of one comment block (their margin already stripped) and the
 * width that margin will reclaim, it walks the block and hands each run of
 * prose and each list item to a paragraph reflow, gathering the canonical lines
 * and the indices of the input lines that overflow or wrap prematurely. Tags,
 * directives, fenced or indented code, tables and separators are left verbatim
 * and bound the paragraph around them.
 *
 * Widths count Unicode code points and whitespace is matched as ASCII only, so
 * the output is byte-for-byte identical to the PHP engine (mb_strlen and the
 * non-unicode PCRE `\s`).
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */

import { KIND, classify, isFence, listMarker } from './comment-classifier.js';
import { reflowParagraph } from './comment-paragraph.js';

export { KIND, classify, isFence, listMarker } from './comment-classifier.js';
export { tokenize } from './comment-tokenizer.js';

/**
 * Reflow a block's content lines to the greedy canonical form, returning the
 * canonical lines and the indices of the input lines that overflow or wrap
 * prematurely.
 */
export function reflow(lines, marginWidth, maxLength) {
    const out = [];
    const long = [];
    const premature = [];
    let inFence = false;
    let i = 0;

    while (i < lines.length) {
        const type = inFence ? KIND.FENCE : classify(lines[i], false);

        if (inFence) {
            out.push(lines[i]);
            inFence = !isFence(lines[i]);
            i += 1;
        } else {
            i = consume(lines, i, type, { out, long, premature, marginWidth, maxLength, fence: value => { inFence = value; } });
        }
    }

    return { lines: out, long, premature };
}

/** Handle one line or paragraph, appending its output and any faults. */
function consume(lines, i, type, ctx) {
    if (type === KIND.PROSE) {
        return paragraph(lines, i, null, ctx);
    }
    if (type === KIND.LIST) {
        return paragraph(lines, i, listMarker(lines[i]), ctx);
    }

    ctx.out.push(lines[i]);

    if (type === KIND.FENCE) {
        ctx.fence(true);
    }

    return i + 1;
}

/**
 * Append a run of prose or a list item, reflowed where it faults. A list line
 * always parses to a marker, so it never reaches the plain-prose branch.
 */
function paragraph(lines, start, marker, ctx) {
    const end = proseEnd(lines, marker === null ? start : start + 1);
    const slice = lines.slice(start, end);
    const segment = reflowParagraph(slice, marker, ctx.marginWidth, ctx.maxLength, start);

    ctx.out.push(...segment.lines);
    segment.long.forEach(offset => ctx.long.push(offset));
    segment.premature.forEach(offset => ctx.premature.push(offset));

    return end;
}

/** The index one past the last prose line from the given start. */
function proseEnd(lines, from) {
    let end = from;

    while (end < lines.length && classify(lines[end], false) === KIND.PROSE) {
        end += 1;
    }

    return end;
}
