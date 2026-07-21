/**
 * Deterministic greedy re-wrapper for comment prose.
 *
 * The syntax-only counterpart of the PHP comment line length sniff: given the
 * content lines of one comment block (their margin already stripped) and the
 * width that margin will reclaim, it reflows each run of prose and each list
 * item to fill every line as far as the maximum width allows, never splitting a
 * word. Overflowing and prematurely wrapped lines are reported on their own
 * indices. Tags, directives, fenced or indented code, tables and separators are
 * left verbatim and bound the paragraph around them; a paragraph that cannot be
 * reflowed safely is left untouched.
 *
 * Widths count Unicode code points and whitespace is matched as ASCII only, so
 * the output is byte-for-byte identical to the PHP engine (mb_strlen and the
 * non-unicode PCRE `\s`).
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */

const WS = '[ \\t\\n\\r\\f\\v]';
const FENCE = /^(```|~~~)/;
const TAG = new RegExp(`^@[A-Za-z][A-Za-z0-9-]*(?=${WS}|$)`);
const LIST = new RegExp(`^${WS}*([-*+]|\\d+[.)])${WS}+`);
const LIST_MARKER = new RegExp(`^(${WS}*)([-*+]|\\d+[.)])${WS}+`);
const DIRECTIVE = /^(phpcs:|phpstan-ignore|@phpstan-|@psalm-|@phan-|eslint-disable|eslint-enable|@ts-|prettier-ignore|stylelint-|@codingStandards|@SuppressWarnings|NOSONAR|qlty-ignore)/;
const SEPARATOR = /^[=\-~*_#.+ ]{3,}$/;
const CODE = new RegExp(`=>|->|::|;${WS}*$|\\{${WS}*$|^\\}|^(?:if|elseif|for|foreach|while|switch|catch)${WS}*\\(|^[\\w$>[\\]'.-]+${WS}*=[^=>]|^\\$`);
const POISON = /^(@[A-Za-z][A-Za-z0-9-]*|[-*+]|\d+[.)])$/;
const SPAN = /`[^`]*`|\{@[^}]*\}|\[[^\]]*\]\([^)]*\)/g;
const ASCII_TRIM = /^[ \t\n\r\0\v]+|[ \t\n\r\0\v]+$/g;
const ASCII_TRIM_START = /^[ \t\n\r\0\v]+/;

/** The kinds a comment content line can take. Only prose and lists reflow. */
export const KIND = {
    BLANK: 'blank',
    PROSE: 'prose',
    LIST: 'list',
    TAG: 'tag',
    DIRECTIVE: 'directive',
    SEPARATOR: 'separator',
    TABLE: 'table',
    FENCE: 'fence',
    CODE: 'code',
};

/** The number of Unicode code points in a string, matching PHP mb_strlen. */
function len(text) {
    return [...text].length;
}

/** Strip leading and trailing ASCII whitespace, matching PHP trim. */
function trimAscii(text) {
    return text.replace(ASCII_TRIM, '');
}

/** Strip leading ASCII whitespace, matching PHP ltrim. */
function trimAsciiStart(text) {
    return text.replace(ASCII_TRIM_START, '');
}

/**
 * Classify a content line, given whether it sits inside a fenced block.
 */
export function classify(content, inFence) {
    const trimmed = trimAscii(content);

    if (inFence) return KIND.FENCE;
    if (trimmed === '') return KIND.BLANK;
    if (FENCE.test(trimmed)) return KIND.FENCE;
    if (DIRECTIVE.test(trimmed)) return KIND.DIRECTIVE;
    if (TAG.test(trimmed)) return KIND.TAG;
    if (trimmed.startsWith('|')) return KIND.TABLE;
    if (SEPARATOR.test(trimmed)) return KIND.SEPARATOR;
    if (LIST.test(content)) return KIND.LIST;
    if (CODE.test(trimmed.replace(SPAN, ''))) return KIND.CODE;

    return KIND.PROSE;
}

/** Whether a content line opens or closes a fenced block. */
export function isFence(content) {
    return FENCE.test(trimAscii(content));
}

/** Parse a list marker into its bullet, indent and occupied width, or null. */
export function listMarker(content) {
    const match = LIST_MARKER.exec(content);

    if (!match) return null;

    return { marker: match[2], indent: len(match[1]), width: len(match[2]) + 1 };
}

/**
 * Split a line of prose into tokens, keeping inline code, link tags and
 * Markdown links whole even when they hold spaces.
 */
export function tokenize(text) {
    const chars = [...text];
    const tokens = [];
    let i = 0;

    while (i < chars.length) {
        if (chars[i] === ' ') {
            i += 1;
            continue;
        }

        const start = i;
        i = consumeToken(chars, i);
        tokens.push(chars.slice(start, i).join(''));
    }

    return tokens;
}

/** Advance past one token, stepping over any atomic span it contains. */
function consumeToken(chars, i) {
    while (i < chars.length && chars[i] !== ' ') {
        if (chars[i] === '`') i = consumeSpan(chars, i, '`');
        else if (chars[i] === '{' && chars[i + 1] === '@') i = consumeSpan(chars, i, '}');
        else if (chars[i] === '[') i = consumeLink(chars, i);
        else i += 1;
    }

    return i;
}

/** Advance past a delimited span to its closer, or by one when it never closes. */
function consumeSpan(chars, i, close) {
    const found = chars.indexOf(close, i + 1);

    return found === -1 ? i + 1 : found + 1;
}

/** Advance past a Markdown link, or by one when the shape does not hold. */
function consumeLink(chars, i) {
    const label = consumeSpan(chars, i, ']');

    if (label === i + 1 || chars[label] !== '(') return i + 1;

    const target = consumeSpan(chars, label, ')');

    return target === label + 1 ? i + 1 : target;
}

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
    if (type === KIND.FENCE) {
        ctx.out.push(lines[i]);
        ctx.fence(true);

        return i + 1;
    }

    if (type === KIND.PROSE) return paragraph(lines, i, null, ctx);
    if (type === KIND.LIST) return paragraph(lines, i, listMarker(lines[i]), ctx);

    ctx.out.push(lines[i]);

    return i + 1;
}

/**
 * Append a run of prose or a list item, reflowed where it faults. A list line
 * always parses to a marker, so it never reaches the plain-prose branch.
 */
function paragraph(lines, start, marker, ctx) {
    const end = proseEnd(lines, marker === null ? start : start + 1);
    const slice = lines.slice(start, end);
    emit(slice, marker, start, ctx);

    return end;
}

/** The index one past the last prose line from the given start. */
function proseEnd(lines, from) {
    let end = from;

    while (end < lines.length && classify(lines[end], false) === KIND.PROSE) end += 1;

    return end;
}

/** Reflow a paragraph and record its output and faults, or emit it verbatim. */
function emit(slice, marker, base, ctx) {
    const wrapped = reflowParagraph(slice, marker, ctx.marginWidth, ctx.maxLength);
    const faults = wrapped === null ? { long: [], premature: [] } : findFaults(slice, ctx.marginWidth, ctx.maxLength);

    if (wrapped === null || (faults.long.length === 0 && faults.premature.length === 0)) {
        ctx.out.push(...slice);

        return;
    }

    ctx.out.push(...wrapped);
    faults.long.forEach(offset => ctx.long.push(base + offset));
    faults.premature.forEach(offset => ctx.premature.push(base + offset));
}

/**
 * The greedy canonical lines of a paragraph, or null when it holds an
 * unwrappable token, is too narrow, or would re-classify once wrapped.
 */
function reflowParagraph(slice, marker, marginWidth, maxLength) {
    const indent = marker === null ? leadingSpaces(slice[0]) : marker.indent;
    const hanging = indent + (marker === null ? 0 : marker.width);
    const width = maxLength - marginWidth - hanging;
    const tokens = glue(tokenize(joinText(slice, marker)));

    if (width < 1 || tokens.length === 0 || longestToken(tokens) > width) return null;

    const lines = layout(greedy(tokens, width), marker, indent, hanging);

    return stable(lines, marker) ? lines : null;
}

/** Assemble output lines, prefixing the marker then the hanging indent. */
function layout(wrapped, marker, indent, hanging) {
    return wrapped.map((text, offset) => (
        offset === 0 && marker !== null
            ? `${' '.repeat(indent)}${marker.marker} ${text}`
            : `${' '.repeat(hanging)}${text}`
    ));
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
        if (marginWidth + len(content) > maxLength) long.push(offset);
        if (wrapsEarly(slice, offset, marginWidth, maxLength)) premature.push(offset);
    });

    return { long, premature };
}

/** Whether a line could hold the first word of the next line within the width. */
function wrapsEarly(slice, offset, marginWidth, maxLength) {
    if (offset + 1 >= slice.length) return false;

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
        if (out.length > 0 && POISON.test(token)) out[out.length - 1] += ` ${token}`;
        else out.push(token);
    }

    return out;
}

/** Fill lines greedily with whole tokens up to the width. */
function greedy(tokens, width) {
    const lines = [];
    let current = '';

    for (const token of tokens) {
        if (current === '') current = token;
        else if (len(current) + 1 + len(token) <= width) current += ` ${token}`;
        else {
            lines.push(current);
            current = token;
        }
    }

    if (current !== '') lines.push(current);

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
