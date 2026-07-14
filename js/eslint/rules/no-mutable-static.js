import { ASTUtils, ESLintUtils } from '@typescript-eslint/utils';

const createRule = ESLintUtils.RuleCreator(
    name => `https://github.com/sinemacula/coding-standards#${name}`,
);

/**
 * Whether a node sits in an ambient context (a `declare` block or a .d.ts file),
 * where a declaration describes existing shape rather than creating runtime state.
 */
function isAmbient(node, filename) {
    if (filename.endsWith('.d.ts')) {
        return true;
    }

    for (let current = node; current; current = current.parent) {
        if (current.declare === true) {
            return true;
        }
    }

    return false;
}

/**
 * Whether the file path marks it as test code, where mutable statics track state
 * across assertions rather than introduce production global state.
 */
function isTestPath(filename) {
    const path = filename.replace(/\\/g, '/');

    return path.includes('/tests/')
        || path.includes('/__tests__/')
        || /\.(test|spec)\.[cm]?[jt]sx?$/.test(path);
}

/**
 * Collect the bound identifiers of a declarator target, unwrapping destructuring.
 */
function boundIdentifiers(pattern, out) {
    switch (pattern.type) {
        case 'Identifier':
            out.push(pattern);
            break;
        case 'ArrayPattern':
            for (const element of pattern.elements) {
                if (element) {
                    boundIdentifiers(element.type === 'RestElement' ? element.argument : element, out);
                }
            }
            break;
        case 'ObjectPattern':
            for (const property of pattern.properties) {
                boundIdentifiers(property.type === 'RestElement' ? property.argument : property.value, out);
            }
            break;
        case 'AssignmentPattern':
            boundIdentifiers(pattern.left, out);
            break;
    }
}

/**
 * Whether a resolved binding is a mutable local `let`/`var` (not const, class,
 * function, import or an ambient declaration), and so publishes live module state.
 */
function bindsMutableVariable(variable, filename) {
    if (filename.endsWith('.d.ts')) {
        return false;
    }

    return variable.defs.some(
        def => def.type === 'Variable' && def.parent.declare !== true && def.parent.kind !== 'const',
    );
}

/**
 * The nearest enclosing class of a node, or null when it sits outside one.
 */
function nearestClass(ancestors) {
    for (let i = ancestors.length - 1; i >= 0; i--) {
        if (ancestors[i].type === 'ClassDeclaration' || ancestors[i].type === 'ClassExpression') {
            return ancestors[i];
        }
    }

    return null;
}

/**
 * Whether the class reads as a test class (by its own or its parent's name).
 */
function isTestClass(klass) {
    if (klass.id?.name?.endsWith('Test')) {
        return true;
    }

    const parent = klass.superClass;

    if (parent?.type === 'Identifier') {
        return parent.name.endsWith('TestCase');
    }

    if (parent?.type === 'MemberExpression' && parent.property.type === 'Identifier') {
        return parent.property.name.endsWith('TestCase');
    }

    return false;
}

/**
 * Whether the docblock immediately preceding a node carries the @managed-static
 * opt-out tag, marking a deliberately mutated static as allowed.
 */
function hasManagedTag(node, sourceCode) {
    const doc = sourceCode.getCommentsBefore(node).at(-1);

    return doc?.type === 'Block' && /(?:^|[\s*])@managed-static(?![-\w])/i.test(doc.value);
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
 * Forbids mutable module-level and class-level static state: exported `let`/`var`
 * bindings (declared inline or published through an `export { ... }` specifier)
 * and non-readonly `static` class fields, including `static accessor`
 * auto-accessors. All are global mutable state; `const` and `readonly` express the
 * read-only configuration this allows.
 *
 * The check is syntactic, not write-sensitive: a static is flagged whether or not
 * a reassignment is visible, since cross-file writes are out of a per-file rule's
 * reach. Deliberately mutated statics opt out with a `@managed-static` doc tag on
 * the field or its declaring class, and test classes are exempt. Module scope is
 * limited to exported bindings; an unexported module `let` stays local and is left
 * alone.
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

        /** Whether a static member is exempt: test code or an opted-out declaration. */
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

        /** Flag a non-readonly static field or auto-accessor as mutable static state. */
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
                        context.report({
                            node: identifier,
                            messageId: 'mutableExport',
                            data: { name: identifier.name },
                        });
                    }

                    return;
                }

                // An `export { x }` specifier publishes a live binding, so a mutable
                // local let/var becomes observable module state. A re-export from
                // another module carries no local binding and type-only exports carry
                // no runtime binding.
                if (node.source || node.exportKind === 'type') {
                    return;
                }

                const scope = sourceCode.getScope(node);

                for (const specifier of node.specifiers) {
                    if (specifier.exportKind === 'type' || specifier.local.type !== 'Identifier') {
                        continue;
                    }

                    const variable = ASTUtils.findVariable(scope, specifier.local);

                    if (!variable || !bindsMutableVariable(variable, context.filename)) {
                        continue;
                    }

                    context.report({
                        node: specifier.local,
                        messageId: 'mutableExport',
                        data: { name: specifier.local.name },
                    });
                }
            },
            PropertyDefinition: inspectStatic,
            AccessorProperty: inspectStatic,
        };
    },
});
