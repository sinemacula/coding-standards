import { ESLintUtils } from '@typescript-eslint/utils';

/** Shared rule factory linking each rule to its documentation anchor. */
export const createRule = ESLintUtils.RuleCreator(
    name => `https://github.com/sinemacula/coding-standards#${name}`,
);

/** Whether the file is a TypeScript declaration file (.d.ts, .d.mts, .d.cts). */
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

/** Whether the class reads as a test class (by its own or its parent's name). */
export function isTestClass(klass) {
    if (klass.id?.name?.endsWith('Test')) {
        return true;
    }

    const parent = superClassName(klass);

    return parent !== null && parent.endsWith('TestCase');
}
