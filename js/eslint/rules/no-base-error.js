import { createRule, isTestPath } from './lib.js';

/** Global objects whose `Error` member resolves to the base `Error`. */
const GLOBAL_OBJECTS = new Set(['globalThis', 'window', 'global', 'self']);

/** Strips the `as`, `satisfies` and non-null wrappers a throw argument may carry. */
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

/** Whether a construction callee denotes the base `Error`, directly or through a global object. */
function isBaseError(callee) {
    if (callee.type === 'Identifier') {
        return callee.name === 'Error';
    }

    return isGlobalError(callee);
}

/**
 * Disallow throwing the base `Error`.
 *
 * A bare `throw new Error(...)`, including the argument-free `throw new Error`,
 * gives a caller no type to catch on, so a domain-specific subclass such as
 * `ValidationError` must be thrown instead.
 *
 * The check is syntactic. It flags a throw whose argument constructs the base
 * `Error`, whether named directly (`new Error()`) or reached through a global
 * object (`new globalThis.Error()`, `new window.Error()`). A `throw ... as X`,
 * `throw ...!` or `satisfies` annotation is unwrapped before the construction is
 * inspected, so the annotation cannot hide the throw. Subclasses
 * (`new NotFoundError()`) and the specific built-ins (`new TypeError()`) read as
 * domain-specific and pass; a re-thrown variable (`throw err`), a qualified name
 * (`new foo.Error()`) and a base `Error` built for a non-throw use
 * (`const e = new Error()`) fall outside the pattern. Only the base `Error` is
 * ever a candidate, so listing `Error` in the `allow` option is the one way to
 * permit it; any other name has nothing to match and stays inert.
 *
 * Test files are exempt: stubs and fakes legitimately throw the base `Error`
 * (`throw new Error('not implemented')`), where a domain subclass adds nothing.
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
