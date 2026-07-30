/**
 * Classifies a single content line of a comment.
 *
 * Only prose and list items are reflowed; every other kind is preserved
 * verbatim and acts as a paragraph boundary. Directives, docblock tags, fenced
 * or indented code, tables and rule separators are left exactly as written, so
 * a reflow never disturbs a construct whose position or spacing carries
 * meaning. The directive set spans the tools that read instructions out of a
 * comment in any of the governed languages, YAML's included: a schema
 * association or a Renovate manager hint is machine-read, so wrapping it would
 * silently sever it from the key it annotates.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */

import { len, trimAscii } from './comment-tokenizer.js';

const WS = '[ \\t\\n\\r\\f\\v]';
const FENCE = /^(```|~~~)/;
const TAG = new RegExp(`^@[A-Za-z][A-Za-z0-9-]*(?=${WS}|$)`);
const LIST = new RegExp(`^${WS}*([-*+]|\\d+[.)])${WS}+`);
const HEADING = new RegExp(`^#{1,6}${WS}`);
const DIRECTIVE = /^(phpcs:|phpstan-ignore|@phpstan-|@psalm-|@phan-|eslint\b|globals?\b|exported\b|biome-ignore\b|@ts-|prettier-ignore|stylelint-|Stryker (?:disable|restore)\b|(?:c8|v8|istanbul) ignore\b|@vite-ignore\b|webpackChunkName\b|@preserve\b|@license\b|@codingStandards|@SuppressWarnings|NOSONAR|qlty-ignore|yamllint\b|yaml-language-server\b|renovate:)/;
const SEPARATOR = /^[=\-~*_#.+ ]{3,}$/;
const CODE = new RegExp(`=>|->|::|;${WS}*$|\\{${WS}*$|^\\}|^(?:if|elseif|for|foreach|while|switch|catch)${WS}*\\(|^[\\w$>[\\]'.-]+${WS}*=[^=>]|^\\$`);
const SPAN = /`[^`]*`|\{@[^}]*\}|\[[^\]]*\]\([^)]*\)/g;
const TYPE_TAG = /^@(?:(?:phpstan|psalm|phan)-(?:type|import-type|param|return|var)|var|param|return|typedef|type|property|returns)\b/;
const TYPE_CONTINUES = /[{<(,:|&]\s*$/;
const QUOTE = /'[^']*'|"[^"]*"/g;
const INDENT = /^(?: {2,}|\t)/;
const LEADING_SPACES = /^ +/;
const ARROWS = /->|=>/g;
const OPENERS = /[{<(]/g;
const CLOSERS = /[}>)]/g;

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
    HEADING: 'heading',
};

/** The ordered rules mapping the first matching leading mark to a kind. */
const KINDS = [
    { kind: KIND.FENCE, test: ({ trimmed }) => FENCE.test(trimmed) },
    { kind: KIND.DIRECTIVE, test: ({ trimmed }) => DIRECTIVE.test(trimmed) },
    { kind: KIND.TAG, test: ({ trimmed }) => TAG.test(trimmed) },
    { kind: KIND.TABLE, test: ({ trimmed }) => trimmed.startsWith('|') },
    { kind: KIND.HEADING, test: ({ trimmed }) => HEADING.test(trimmed) },
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

/** Whether a content line is indented past the baseline: a tab or two spaces. */
export function indented(content) {
    return INDENT.test(content);
}

/** The number of leading spaces on a content line, for the hanging-indent bound. */
export function indentWidth(content) {
    return content.length - content.replace(LEADING_SPACES, '').length;
}

/** Whether a line ends the verbatim type block before it: a blank line or a fresh tag. */
export function isTypeBoundary(content) {
    const trimmed = trimAscii(content);

    return trimmed === '' || trimmed.startsWith('@');
}

/** The net depth a line's type slot opens, ignoring inline spans and arrows. */
export function bracketDelta(text) {
    const stripped = typeSlot(text).replace(ARROWS, '');

    return (stripped.match(OPENERS) ?? []).length - (stripped.match(CLOSERS) ?? []).length;
}

/** The depth a type-annotation tag opens, or zero when it is not one or closes here. */
export function typeTagDepth(content) {
    if (!TYPE_TAG.test(trimAscii(content))) {
        return 0;
    }

    const delta = bracketDelta(content);

    return delta > 0 && TYPE_CONTINUES.test(typeSlot(content)) ? delta : 0;
}

/** The type-carrying portion of a line: spans and quotes removed, and everything from the first variable dropped. */
function typeSlot(content) {
    const stripped = content.replace(SPAN, '').replace(QUOTE, '');
    const variable = stripped.indexOf('$');

    return variable === -1 ? stripped : stripped.slice(0, variable);
}
