import {
    createRule,
    isAmbient,
    isDeclarationFile,
    isTestClass,
    isTestPath,
    nearestClass,
    superClassName,
} from './lib.js';

/**
 * Require the `readonly` modifier on public class properties, declared or
 * constructor-promoted.
 *
 * A public auto-accessor has no readonly form, so it is disallowed outright.
 * Statics are left to `no-mutable-static`.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
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

        if (isDeclarationFile(context.filename) || isTestPath(context.filename)) {
            return {};
        }

        /** Whether the node's enclosing class is exempt from the mandate. */
        const isExempt = node => {
            if (isAmbient(node, context.filename)) {
                return true;
            }

            const klass = nearestClass(sourceCode.getAncestors(node));

            return klass !== null
                && (isTestClass(klass) || isIgnoredParent(klass, ignoredParents));
        };

        return {
            'PropertyDefinition, TSAbstractPropertyDefinition'(node) {
                if (isOutOfScope(node) || node.readonly || isExempt(node)) {
                    return;
                }

                context.report({
                    node: node.key,
                    messageId: 'mutable',
                    data: { name: propertyName(node.key, sourceCode) },
                });
            },
            'AccessorProperty, TSAbstractAccessorProperty'(node) {
                if (isOutOfScope(node) || isExempt(node)) {
                    return;
                }

                context.report({
                    node: node.key,
                    messageId: 'accessor',
                    data: { name: propertyName(node.key, sourceCode) },
                });
            },
            TSParameterProperty(node) {
                if (node.readonly || node.accessibility !== 'public' || isExempt(node)) {
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

/** Whether the property's accessibility marks it non-public. */
function isNonPublic(node) {
    return node.key.type === 'PrivateIdentifier'
        || node.accessibility === 'private'
        || node.accessibility === 'protected';
}

/** Whether a class property is static, ambient or non-public. */
function isOutOfScope(node) {
    return node.static || node.declare || isNonPublic(node);
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
