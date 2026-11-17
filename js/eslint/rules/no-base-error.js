import { createRule, isTestPath } from './lib.js';

/** Global objects whose `Error` member resolves to the base `Error`. */
const GLOBAL_OBJECTS = new Set(['globalThis', 'window', 'global', 'self']);

/** Strips `as`, `satisfies` and non-null wrappers from a throw argument. */
function unwrapType(node) {
    let current = node;

    while (
        current.type === 'TSAsExpression'
        || current.type === 'TSSatisfiesExpression'
        || current.type === 'TSNonNullExpression'
    ) {
        current = current.expression;
    }

    return current;
}

/** Whether a node is a reference to one of the recognised global objects. */
function isGlobalObjectRef(node) {
    return node.type === 'Identifier' && GLOBAL_OBJECTS.has(node.name);
}

/** Whether a node is the identifier `Error`. */
function isErrorProperty(node) {
    return node.type === 'Identifier' && node.name === 'Error';
}

/** Whether a member expression reads as `<global>.Error`. */
function isGlobalError(callee) {
    return callee.type === 'MemberExpression'
        && !callee.computed
        && isGlobalObjectRef(callee.object)
        && isErrorProperty(callee.property);
}

/** Whether a callee is the base `Error`, directly or via a global object. */
function isBaseError(callee) {
    if (callee.type === 'Identifier') {
        return callee.name === 'Error';
    }

    return isGlobalError(callee);
}

/**
 * Disallow throwing the base `Error`, directly or via a global object such as
 * `globalThis`, since it gives a caller no type to catch on.
 *
 * The check is syntactic; a type assertion around the throw is unwrapped first.
 * Only `Error` in the `allow` option has any effect. Test files are exempt.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */
export default createRule({
    name: 'no-base-error',
    meta: {
        type: 'problem',
        docs: {
            description: 'Disallow throwing the base Error in favour of a domain-specific subclass.',
        },
        schema: [
            {
                type: 'object',
                properties: {
                    allow: {
                        type: 'array',
                        items: { type: 'string' },
                    },
                },
                additionalProperties: false,
            },
        ],
        messages: {
            baseError: 'Throw a domain-specific Error subclass, not the base Error.',
        },
    },
    defaultOptions: [{ allow: [] }],
    create(context, [options]) {
        if (isTestPath(context.filename)) {
            return {};
        }

        const allow = options.allow ?? [];

        return {
            ThrowStatement(node) {
                const thrown = unwrapType(node.argument);

                if (thrown.type !== 'NewExpression' || !isBaseError(thrown.callee)) {
                    return;
                }

                if (allow.includes('Error')) {
                    return;
                }

                context.report({ node: thrown, messageId: 'baseError' });
            },
        };
    },
});
