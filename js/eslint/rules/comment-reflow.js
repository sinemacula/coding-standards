/**
 * Deterministic greedy re-wrapper for comment prose.
 *
 * The syntax-only counterpart of the PHP comment line length sniff: given the
 * content lines of one comment block (their margin already stripped) and the
 * width that margin will reclaim, it walks the block and hands each run of
 * prose and each list item to a paragraph reflow, gathering the canonical lines
 * and the indices of the input lines that overflow or wrap prematurely. A
 * fenced block, a type-annotation tag whose value opens a bracketed type across
 * lines, headings, indented code, tags, directives, tables and separators are
 * left verbatim and bound the paragraph around them.
 *
 * Widths count Unicode code points and whitespace is matched as ASCII only, so
 * the output is byte-for-byte identical to the PHP engine (mb_strlen and the
 * non-unicode PCRE `\s`).
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */

import { KIND, bracketDelta, classify, indentWidth, indented, isFence, isTypeBoundary, listMarker, typeTagDepth } from './comment-classifier.js';
import { reflowParagraph } from './comment-paragraph.js';

export { KIND, classify, isFence, listMarker } from './comment-classifier.js';
export { tokenize } from './comment-tokenizer.js';

/**
 * Reflow a block's content lines to the greedy canonical form, returning the
 * canonical lines and the indices of the input lines that overflow or wrap
 * prematurely.
 */
export function reflow(lines, marginWidth, maxLength) {
    const ctx = { out: [], long: [], premature: [], marginWidth, maxLength, inFence: false, typeDepth: 0 };
    let i = 0;

    while (i < lines.length) {
        i = step(lines, i, ctx);
    }

    return { lines: ctx.out, long: ctx.long, premature: ctx.premature };
}

/**
 * Advance one line, keeping verbatim any line inside a fenced block or inside a
 * type-annotation tag whose bracketed value is still open.
 */
function step(lines, i, ctx) {
    const line = lines[i];

    if (ctx.inFence) {
        ctx.inFence = !isFence(line);

        return verbatim(line, i, ctx);
    }

    if (ctx.typeDepth > 0 && !isTypeBoundary(line)) {
        ctx.typeDepth = Math.max(0, ctx.typeDepth + bracketDelta(line));

        return verbatim(line, i, ctx);
    }

    ctx.typeDepth = 0;

    const opening = typeTagDepth(line);

    if (opening > 0) {
        ctx.typeDepth = opening;

        return verbatim(line, i, ctx);
    }

    return consume(lines, i, ctx);
}

/** Dispatch a line by kind: an indented line is verbatim; else fence, prose or list. */
function consume(lines, i, ctx) {
    if (indented(lines[i])) {
        return verbatim(lines[i], i, ctx);
    }

    const type = classify(lines[i], false);

    if (type === KIND.PROSE) {
        return paragraph(lines, i, null, ctx);
    }
    if (type === KIND.LIST) {
        return paragraph(lines, i, listMarker(lines[i]), ctx);
    }
    if (type === KIND.FENCE) {
        ctx.inFence = true;
    }

    return verbatim(lines[i], i, ctx);
}

/** Emit one line unchanged and advance past it. */
function verbatim(line, i, ctx) {
    ctx.out.push(line);

    return i + 1;
}

/**
 * Append a run of prose or a list item, reflowed where it faults. A list line
 * always parses to a marker, so it never reaches the plain-prose branch.
 */
function paragraph(lines, start, marker, ctx) {
    const end = marker === null ? flushEnd(lines, start) : continuationEnd(lines, start + 1, marker.indent + marker.width);
    const slice = lines.slice(start, end);
    const segment = reflowParagraph(slice, marker, ctx.marginWidth, ctx.maxLength, start);

    ctx.out.push(...segment.lines);
    segment.long.forEach(offset => ctx.long.push(offset));
    segment.premature.forEach(offset => ctx.premature.push(offset));

    return end;
}

/**
 * The index one past the last flush prose line from the given start. An
 * indented line ends the paragraph, bounding a verbatim indented code block.
 */
function flushEnd(lines, from) {
    let end = from;

    while (end < lines.length && classify(lines[end], false) === KIND.PROSE && !indented(lines[end])) {
        end += 1;
    }

    return end;
}

/**
 * The index one past the last continuation line of a list item. A line indented
 * past the hanging indent is verbatim code, not a continuation.
 */
function continuationEnd(lines, from, hanging) {
    let end = from;

    while (end < lines.length && classify(lines[end], false) === KIND.PROSE && indentWidth(lines[end]) <= hanging) {
        end += 1;
    }

    return end;
}
