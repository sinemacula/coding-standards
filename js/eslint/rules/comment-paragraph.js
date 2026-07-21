/**
 * Reflows a single paragraph of comment prose to its greedy canonical form.
 *
 * A paragraph is a run of prose lines, or one list item with its continuation
 * lines. It is filled line by line with as many whole words as the width
 * allows, never splitting a word and keeping any list marker's hanging indent.
 * A paragraph is reflowed only when it is safe to: it holds no token wider than
 * the line, is not too narrow, and would still read as prose once wrapped.
 * Otherwise it is returned untouched.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */

import { KIND, LIST_MARKER, classify } from './comment-classifier.js';
import { len, tokenize, trimAsciiStart } from './comment-tokenizer.js';

const POISON = /^(@[A-Za-z][A-Za-z0-9-]*|[-*+]|\d+[.)])$/;

/**
 * Reflow a paragraph, returning its output lines and the faults found, their
 * indices offset from the given base, or the verbatim input when it cannot be
 * reflowed or carries no fault.
 */
export function reflowParagraph(slice, marker, marginWidth, maxLength, base) {
    const wrapped = wrapLines(slice, marker, marginWidth, maxLength);
    const faults = wrapped === null ? { long: [], premature: [] } : findFaults(slice, marginWidth, maxLength);

    if (wrapped === null || (faults.long.length === 0 && faults.premature.length === 0)) {
        return { lines: slice, long: [], premature: [] };
    }

    return {
        lines: wrapped,
        long: faults.long.map(offset => base + offset),
        premature: faults.premature.map(offset => base + offset),
    };
}

/**
 * The greedy canonical lines of a paragraph, or null when it holds an
 * unwrappable token, is too narrow, or would re-classify once wrapped.
 */
function wrapLines(slice, marker, marginWidth, maxLength) {
    const indent = marker === null ? leadingSpaces(slice[0]) : marker.indent;
    const hanging = indent + (marker === null ? 0 : marker.width);
    const width = maxLength - marginWidth - hanging;
    const tokens = glue(tokenize(joinText(slice, marker)));

    if (width < 1 || tokens.length === 0 || longestToken(tokens) > width) {
        return null;
    }

    const lines = layout(greedy(tokens, width), marker, indent, hanging);

    return stable(lines, marker) ? lines : null;
}

/** Assemble output lines, prefixing the marker then the hanging indent. */
function layout(wrapped, marker, indent, hanging) {
    return wrapped.map((text, offset) =>
        offset === 0 && marker !== null
            ? `${' '.repeat(indent)}${marker.marker} ${text}`
            : `${' '.repeat(hanging)}${text}`,
    );
}

/** Whether every reflowed line still classifies as it must. */
function stable(lines, marker) {
    return lines.every((line, offset) => {
        const expected = offset === 0 && marker !== null ? KIND.LIST : KIND.PROSE;

        return classify(line, false) === expected;
    });
}

/** The input offsets that overflow the width and those that wrap early. */
function findFaults(slice, marginWidth, maxLength) {
    const long = [];
    const premature = [];

    slice.forEach((content, offset) => {
        if (marginWidth + len(content) > maxLength) {
            long.push(offset);
        }
        if (wrapsEarly(slice, offset, marginWidth, maxLength)) {
            premature.push(offset);
        }
    });

    return { long, premature };
}

/** Whether a line could hold the first word of the next line within the width. */
function wrapsEarly(slice, offset, marginWidth, maxLength) {
    if (offset + 1 >= slice.length) {
        return false;
    }

    const next = glue(tokenize(slice[offset + 1]))[0] ?? '';

    return next !== '' && marginWidth + len(slice[offset]) + 1 + len(next) <= maxLength;
}

/** Join a paragraph's lines, stripping any list marker from the first line. */
function joinText(slice, marker) {
    const first = marker === null ? trimAsciiStart(slice[0]) : slice[0].replace(LIST_MARKER, '');
    const rest = slice.slice(1).map(line => trimAsciiStart(line));

    return [first, ...rest].join(' ');
}

/** Merge backward any token that must never open a wrapped line. */
function glue(tokens) {
    const out = [];

    for (const token of tokens) {
        if (out.length > 0 && POISON.test(token)) {
            out[out.length - 1] += ` ${token}`;
        } else {
            out.push(token);
        }
    }

    return out;
}

/** Fill lines greedily with whole tokens up to the width. */
function greedy(tokens, width) {
    const lines = [];
    let current = '';

    for (const token of tokens) {
        if (current === '') {
            current = token;
        } else if (len(current) + 1 + len(token) <= width) {
            current += ` ${token}`;
        } else {
            lines.push(current);
            current = token;
        }
    }

    if (current !== '') {
        lines.push(current);
    }

    return lines;
}

/** The length of the longest token. */
function longestToken(tokens) {
    return Math.max(...tokens.map(len));
}

/** The number of leading spaces on a line. */
function leadingSpaces(text) {
    return len(text) - len(trimAsciiStart(text));
}
