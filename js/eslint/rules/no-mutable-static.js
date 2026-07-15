import { ASTUtils } from '@typescript-eslint/utils';
import { createRule, isAmbient, isDeclarationFile, isTestClass, isTestPath, nearestClass } from './lib.js';

/**
 * Unwrap a rest element to its bound target, else return the node unchanged.
 */
function restTarget(node) {
    return node.type === 'RestElement' ? node.argument : node;
}

/**
 * The destructuring children of a pattern that may bind further identifiers.
 */
function patternChildren(pattern) {
    switch (pattern.type) {
        case 'ArrayPattern':
            return pattern.elements.filter(Boolean).map(restTarget);
        case 'ObjectPattern':
            return pattern.properties.map(
                property => (property.type === 'RestElement' ? property.argument : property.value),
            );
        case 'AssignmentPattern':
            return [pattern.left];
        default:
            return [];
    }
}

/**
 * Collect the bound identifiers of a declarator target, unwrapping
 * destructuring.
 */
function boundIdentifiers(pattern, out) {
    if (pattern.type === 'Identifier') {
        out.push(pattern);
        return;
    }

    for (const child of patternChildren(pattern)) {
        boundIdentifiers(child, out);
    }
}

/**
 * Whether a resolved binding is a mutable local `let`/`var` (not const, class,
 * function, import or an ambient declaration), and so publishes live module
 * state.
 */
function bindsMutableVariable(variable, filename) {
    if (isDeclarationFile(filename)) {
        return false;
    }

    return variable.defs.some(
        def => def.type === 'Variable' && def.parent.declare !== true && def.parent.kind !== 'const',
    );
}

/**
 * Matches @managed-static only at a docblock tag position, never inside prose.
 */
const MANAGED_TAG = /(?:^|[\s*])@managed-static(?![-\w])/i;

/**
 * Whether a @managed-static opt-out docblock precedes the node, including one
 * tucked between a decorator and the member name.
 */
function hasManagedTag(node, sourceCode) {
    const before = sourceCode.getCommentsBefore(node).at(-1);

    if (before?.type === 'Block' && MANAGED_TAG.test(before.value)) {
        return true;
    }

    // A docblock tucked between the decorators and the declaration sits before
    // the first token after the last decorator, not before the whole member.
    if (node.decorators?.length) {
        const afterDecorators = sourceCode.getTokenAfter(node.decorators.at(-1));
        const inner = afterDecorators && sourceCode.getCommentsBefore(afterDecorators).at(-1);

        return inner?.type === 'Block' && MANAGED_TAG.test(inner.value);
    }

    return false;
}

/**
 * A readable name for a class member key, including private and computed forms.
 */
function describeKey(node, sourceCode) {
    if (node.computed) {
        return `[${sourceCode.getText(node.key)}]`;
    }

    if (node.key.type === 'PrivateIdentifier') {
        return `#${node.key.name}`;
    }

    if (node.key.type === 'Literal') {
        return String(node.key.value);
    }

    return node.key.name;
}

/**
 * Report each identifier bound by an inline `export let`/`export var`
 * declaration.
 */
function reportInlineExports(node, context) {
    const declaration = node.declaration;

    if (declaration.type !== 'VariableDeclaration' || declaration.kind === 'const') {
        return;
    }

    if (isAmbient(declaration, context.filename)) {
        return;
    }

    const identifiers = [];

    for (const declarator of declaration.declarations) {
        boundIdentifiers(declarator.id, identifiers);
    }

    for (const identifier of identifiers) {
        context.report({ node: identifier, messageId: 'mutableExport', data: { name: identifier.name } });
    }
}

/** Report `export { x }` specifiers that publish a mutable local binding. */
function reportSpecifierExports(node, context) {
    // A re-export carries no local binding; a type-only export carries no
    // runtime one.
    if (node.source || node.exportKind === 'type') {
        return;
    }

    const scope = context.sourceCode.getScope(node);

    for (const specifier of node.specifiers) {
        if (specifier.exportKind === 'type' || specifier.local.type !== 'Identifier') {
            continue;
        }

        const variable = ASTUtils.findVariable(scope, specifier.local);

        if (variable && bindsMutableVariable(variable, context.filename)) {
            context.report({ node: specifier.local, messageId: 'mutableExport', data: { name: specifier.local.name } });
        }
    }
}

/**
 * Forbids mutable module-level and class-level static state: exported
 * `let`/`var` bindings (declared inline or published through an
 * `export { ... }` specifier) and non-readonly `static` class fields, including
 * `static accessor` auto-accessors. All are global mutable state; `const` and
 * `readonly` express the read-only configuration this allows.
 *
 * The check is syntactic, not write-sensitive: a static is flagged whether or
 * not a reassignment is visible, since cross-file writes are out of a per-file
 * rule's reach. Deliberately mutated statics opt out with a `@managed-static`
 * doc tag on the field or its declaring class, and test classes are exempt.
 * Module scope is limited to exported bindings; an unexported module `let`
 * stays local and is left alone.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */
export default createRule({
    name: 'no-mutable-static',
    meta: {
        type: 'problem',
        docs: {
            description: 'Disallow mutable exported bindings and mutable static class fields.',
        },
        schema: [],
        messages: {
            mutableExport: 'Exported binding "{{ name }}" introduces mutable module state; declare it with const.',
            mutableStatic: 'Static field "{{ name }}" is mutable static state; mark it readonly or use a constant.',
        },
    },
    defaultOptions: [],
    create(context) {
        const { sourceCode } = context;

        /**
         * Whether a static member is exempt: test code or an opted-out
         * declaration.
         */
        const isStaticExempt = node => {
            if (isTestPath(context.filename)) {
                return true;
            }

            const klass = nearestClass(sourceCode.getAncestors(node));

            if (klass !== null && isTestClass(klass)) {
                return true;
            }

            return hasManagedTag(node, sourceCode) || (klass !== null && hasManagedTag(klass, sourceCode));
        };

        /**
         * Flag a non-readonly static field or auto-accessor as mutable static
         * state.
         */
        const inspectStatic = node => {
            if (!node.static || node.readonly || isAmbient(node, context.filename) || isStaticExempt(node)) {
                return;
            }

            context.report({
                node: node.key,
                messageId: 'mutableStatic',
                data: { name: describeKey(node, sourceCode) },
            });
        };

        return {
            ExportNamedDeclaration(node) {
                if (node.declaration) {
                    reportInlineExports(node, context);
                } else {
                    reportSpecifierExports(node, context);
                }
            },
            PropertyDefinition: inspectStatic,
            AccessorProperty: inspectStatic,
        };
    },
});
