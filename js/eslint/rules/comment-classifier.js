/**
 * Classifies a single content line of a comment.
 *
 * Only prose and list items are reflowed; every other kind is preserved
 * verbatim and acts as a paragraph boundary. Directives, docblock tags, fenced
 * or indented code, tables and rule separators are left exactly as written, so
 * a reflow never disturbs a construct whose position or spacing carries
 * meaning.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */

import { len, trimAscii } from './comment-tokenizer.js';

const WS = '[ \\t\\n\\r\\f\\v]';
const FENCE = /^(```|~~~)/;
const TAG = new RegExp(`^@[A-Za-z][A-Za-z0-9-]*(?=${WS}|$)`);
const LIST = new RegExp(`^${WS}*([-*+]|\\d+[.)])${WS}+`);
const DIRECTIVE = /^(phpcs:|phpstan-ignore|@phpstan-|@psalm-|@phan-|eslint-disable|eslint-enable|@ts-|prettier-ignore|stylelint-|@codingStandards|@SuppressWarnings|NOSONAR|qlty-ignore)/;
const SEPARATOR = /^[=\-~*_#.+ ]{3,}$/;
const CODE = new RegExp(`=>|->|::|;${WS}*$|\\{${WS}*$|^\\}|^(?:if|elseif|for|foreach|while|switch|catch)${WS}*\\(|^[\\w$>[\\]'.-]+${WS}*=[^=>]|^\\$`);
const SPAN = /`[^`]*`|\{@[^}]*\}|\[[^\]]*\]\([^)]*\)/g;

/** A list marker: a bullet or an ordered number, and the space after it. */
export const LIST_MARKER = new RegExp(`^(${WS}*)([-*+]|\\d+[.)])${WS}+`);

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

/** The ordered rules mapping the first matching leading mark to a kind. */
const KINDS = [
    { kind: KIND.FENCE, test: ({ trimmed }) => FENCE.test(trimmed) },
    { kind: KIND.DIRECTIVE, test: ({ trimmed }) => DIRECTIVE.test(trimmed) },
    { kind: KIND.TAG, test: ({ trimmed }) => TAG.test(trimmed) },
    { kind: KIND.TABLE, test: ({ trimmed }) => trimmed.startsWith('|') },
    { kind: KIND.SEPARATOR, test: ({ trimmed }) => SEPARATOR.test(trimmed) },
    { kind: KIND.LIST, test: ({ content }) => LIST.test(content) },
    { kind: KIND.CODE, test: ({ trimmed }) => CODE.test(trimmed.replace(SPAN, '')) },
];

/**
 * Classify a content line, given whether it sits inside a fenced block.
 */
export function classify(content, inFence) {
    const trimmed = trimAscii(content);

    if (inFence) {
        return KIND.FENCE;
    }

    return trimmed === '' ? KIND.BLANK : kindOf(content, trimmed);
}

/** Classify a non-blank line by its leading marks, defaulting to prose. */
function kindOf(content, trimmed) {
    const line = { content, trimmed };

    return KINDS.find(({ test }) => test(line))?.kind ?? KIND.PROSE;
}

/** Whether a content line opens or closes a fenced block. */
export function isFence(content) {
    return FENCE.test(trimAscii(content));
}

/** Parse a list marker into its bullet, indent and occupied width, or null. */
export function listMarker(content) {
    const match = LIST_MARKER.exec(content);

    if (!match) {
        return null;
    }

    return { marker: match[2], indent: len(match[1]), width: len(match[2]) + 1 };
}
