/**
 * Shared helpers for the @sinemacula ESLint rules.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */

import { ESLintUtils } from '@typescript-eslint/utils';

/** Shared rule factory linking each rule to its documentation anchor. */
export const createRule = ESLintUtils.RuleCreator(
    name => `https://github.com/sinemacula/coding-standards#${name}`,
);

/**
 * Whether the file is a TypeScript declaration file (.d.ts, .d.mts, .d.cts).
 */
export function isDeclarationFile(filename) {
    return /\.d\.[cm]?ts$/.test(filename);
}

/**
 * Whether the file path marks it as test code, widening the tests/ directory
 * convention to the .test/.spec suffixes and the __tests__ directory.
 */
export function isTestPath(filename) {
    const path = filename.replace(/\\/g, '/');

    return path.includes('/tests/')
        || path.includes('/__tests__/')
        || /\.(test|spec)\.[cm]?[jt]sx?$/.test(path);
}

/**
 * Whether a node sits in an ambient context: a declaration file, or inside a
 * `declare` class, namespace or block, where a declaration describes existing
 * shape rather than creating runtime state.
 */
export function isAmbient(node, filename) {
    if (isDeclarationFile(filename)) {
        return true;
    }

    for (let current = node; current; current = current.parent) {
        if (current.declare === true) {
            return true;
        }
    }

    return false;
}

/** The nearest enclosing class of a node, or null when it sits outside one. */
export function nearestClass(ancestors) {
    for (let i = ancestors.length - 1; i >= 0; i--) {
        if (ancestors[i].type === 'ClassDeclaration' || ancestors[i].type === 'ClassExpression') {
            return ancestors[i];
        }
    }

    return null;
}

/**
 * The simple name of a class's parent, or null when it has none or comes from a
 * computed expression. A qualified parent (`ns.Model`) reduces to its final
 * segment; a mixin-produced base (`mixin(Base)`) has no name.
 */
export function superClassName(klass) {
    const parent = klass.superClass;

    if (parent?.type === 'Identifier') {
        return parent.name;
    }

    if (parent?.type === 'MemberExpression' && parent.property.type === 'Identifier') {
        return parent.property.name;
    }

    return null;
}

/**
 * Whether the class reads as a test class (by its own or its parent's name).
 */
export function isTestClass(klass) {
    if (klass.id?.name?.endsWith('Test')) {
        return true;
    }

    const parent = superClassName(klass);

    return parent !== null && parent.endsWith('TestCase');
}

/** A boundary-anchored matcher for a documentation tag by its bare name. */
export function tagMatcher(tag) {
    const escaped = tag.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');

    return new RegExp(`(?:^|[\\s*])@${escaped}(?![-\\w])`, 'i');
}

/**
 * The file's descriptive docblock: the block comment carrying all of the given
 * tags together, or null when no comment carries them.
 *
 * The block is found by its tags rather than its position, since the header
 * need not open the file: a module documented at its export sits below the
 * imports. Requiring one comment to carry every tag is what makes the match a
 * single block rather than a header split across several.
 */
export function fileDocBlock(sourceCode, tags) {
    const matchers = tags.map(tagMatcher);

    return sourceCode.getAllComments().find(
        comment => comment.type === 'Block' && matchers.every(matcher => matcher.test(comment.value)),
    ) ?? null;
}
