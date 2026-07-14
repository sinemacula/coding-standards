import { ESLintUtils } from '@typescript-eslint/utils';

const createRule = ESLintUtils.RuleCreator(
    name => `https://github.com/sinemacula/coding-standards#${name}`,
);

/**
 * Require the `readonly` modifier on public class properties.
 *
 * Public properties, whether declared or constructor-promoted, must be
 * `readonly`. Mutable public state breaks encapsulation; the legitimate
 * data-holder case is expressed with `public readonly`. Public auto-accessors
 * have no `readonly` form and always expose a setter, so they are disallowed
 * outright. Static properties are left to the mutable static state concern,
 * non-public properties are unaffected, and ambient declarations plus test
 * fixtures are exempt. TypeScript has no whole-class readonly modifier, so
 * there is no class-level exemption.
 */
export default createRule({
    name: 'require-readonly-public-property',
    meta: {
        type: 'problem',
        docs: {
            description: 'Require the readonly modifier on public class properties.',
        },
        schema: [
            {
                type: 'object',
                properties: {
                    ignoredParentClasses: {
                        type: 'array',
                        items: { type: 'string' },
                    },
                },
                additionalProperties: false,
            },
        ],
        messages: {
            mutable: 'Public property "{{ name }}" must be readonly; mutable public state breaks encapsulation.',
            accessor: 'Public auto-accessor "{{ name }}" is mutable; make it non-public or a readonly property.',
        },
    },
    defaultOptions: [{ ignoredParentClasses: [] }],
    create(context, [options]) {
        const ignoredParents = options.ignoredParentClasses ?? [];
        const { sourceCode }  = context;

        // Declaration files and test directories are exempt wholesale.
        if (isDeclarationFile(context.filename) || isTestPath(context.filename)) {
            return {};
        }

        /** Whether the node's enclosing class is exempt from the mandate. */
        const isExempt = node => {
            const ancestors = sourceCode.getAncestors(node);

            if (isAmbient(ancestors)) {
                return true;
            }

            const klass = nearestClass(ancestors);

            return klass !== null
                && (isTestClass(klass) || isIgnoredParent(klass, ignoredParents));
        };

        return {
            'PropertyDefinition, TSAbstractPropertyDefinition'(node) {
                if (
                    node.static
                    || node.readonly
                    || node.declare
                    || node.key.type === 'PrivateIdentifier'
                    || node.accessibility === 'private'
                    || node.accessibility === 'protected'
                    || isExempt(node)
                ) {
                    return;
                }

                context.report({
                    node: node.key,
                    messageId: 'mutable',
                    data: { name: propertyName(node.key, sourceCode) },
                });
            },
            'AccessorProperty, TSAbstractAccessorProperty'(node) {
                // An auto-accessor cannot be readonly, so a public one is always mutable.
                if (
                    node.static
                    || node.declare
                    || node.key.type === 'PrivateIdentifier'
                    || node.accessibility === 'private'
                    || node.accessibility === 'protected'
                    || isExempt(node)
                ) {
                    return;
                }

                context.report({
                    node: node.key,
                    messageId: 'accessor',
                    data: { name: propertyName(node.key, sourceCode) },
                });
            },
            TSParameterProperty(node) {
                if (
                    node.readonly
                    || node.accessibility !== 'public'
                    || isExempt(node)
                ) {
                    return;
                }

                context.report({
                    node: node.parameter,
                    messageId: 'mutable',
                    data: { name: parameterName(node.parameter) },
                });
            },
        };
    },
});

/** Whether the file is a TypeScript declaration file. */
function isDeclarationFile(filename) {
    return /\.d\.[cm]?ts$/.test(filename);
}

/**
 * Whether the file path marks it as test code. The `.test`/`.spec` suffixes and
 * `__tests__` directory deliberately widen the sniff's `tests/`-only detection
 * to the established JS test-file conventions.
 */
function isTestPath(filename) {
    const path = filename.replace(/\\/g, '/');

    return path.includes('/tests/')
        || path.includes('/__tests__/')
        || /\.(test|spec)\.[cm]?[jt]sx?$/.test(path);
}

/** Whether any ancestor puts the node in an ambient (declared) context. */
function isAmbient(ancestors) {
    return ancestors.some(
        node =>
            (node.type === 'ClassDeclaration'
                || node.type === 'ClassExpression'
                || node.type === 'TSModuleDeclaration')
            && node.declare === true,
    );
}

/** The nearest enclosing class, or null when the node sits outside one. */
function nearestClass(ancestors) {
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
 * segment; a mixin-produced base (`mixin(Base)`) has no name and so is never
 * matched, leaving it enforced.
 */
function superClassName(klass) {
    const parent = klass.superClass;

    if (parent === null) {
        return null;
    }

    if (parent.type === 'Identifier') {
        return parent.name;
    }

    if (parent.type === 'MemberExpression' && parent.property.type === 'Identifier') {
        return parent.property.name;
    }

    return null;
}

/** Whether the class reads as a test class (by its own or its parent's name). */
function isTestClass(klass) {
    if (klass.id?.name?.endsWith('Test')) {
        return true;
    }

    const parent = superClassName(klass);

    return parent !== null && parent.endsWith('TestCase');
}

/** Whether the class extends one of the configured exempt parents. */
function isIgnoredParent(klass, ignoredParents) {
    const parent = superClassName(klass);

    return parent !== null && ignoredParents.includes(parent);
}

/** A readable name for a (possibly computed) property key. */
function propertyName(key, sourceCode) {
    if (key.type === 'Identifier') {
        return key.name;
    }

    if (key.type === 'Literal') {
        return String(key.value);
    }

    return sourceCode.getText(key);
}

/** The declared name of a constructor parameter property. */
function parameterName(parameter) {
    if (parameter.type === 'Identifier') {
        return parameter.name;
    }

    if (parameter.type === 'AssignmentPattern' && parameter.left.type === 'Identifier') {
        return parameter.left.name;
    }

    return null;
}
