/**
 * Text primitives for the comment reflow engine.
 *
 * Splits prose into wrapping tokens, keeping inline code, an inline `{@link}`
 * tag and a Markdown link whole even when they hold spaces, and measures widths
 * in Unicode code points. Widths and whitespace match the PHP engine exactly:
 * code points as mb_strlen counts them, and ASCII-only trimming as trim does.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */

const ASCII_TRIM = /^[ \t\n\r\0\v]+|[ \t\n\r\0\v]+$/g;
const ASCII_TRIM_START = /^[ \t\n\r\0\v]+/;

/** The number of Unicode code points in a string, matching PHP mb_strlen. */
export function len(text) {
    return [...text].length;
}

/** Strip leading and trailing ASCII whitespace, matching PHP trim. */
export function trimAscii(text) {
    return text.replace(ASCII_TRIM, '');
}

/** Strip leading ASCII whitespace, matching PHP ltrim. */
export function trimAsciiStart(text) {
    return text.replace(ASCII_TRIM_START, '');
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
        if (chars[i] === '`') {
            i = consumeSpan(chars, i, '`');
        } else if (chars[i] === '{' && chars[i + 1] === '@') {
            i = consumeSpan(chars, i, '}');
        } else if (chars[i] === '[') {
            i = consumeLink(chars, i);
        } else {
            i += 1;
        }
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

    if (label === i + 1 || chars[label] !== '(') {
        return i + 1;
    }

    const target = consumeSpan(chars, label, ')');

    return target === label + 1 ? i + 1 : target;
}
