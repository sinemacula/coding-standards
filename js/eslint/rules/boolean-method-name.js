import * as ts from 'typescript';
import { ESLintUtils } from '@typescript-eslint/utils';
import { createRule, isAmbient } from './lib.js';

/** Copular and modal prefixes that read as predicates. */
const ALLOWED_PREFIXES = new Set([
    'is', 'are', 'was', 'were', 'has', 'have', 'had', 'can', 'could',
    'should', 'shall', 'will', 'would', 'may', 'might', 'must', 'needs',
    'does',
]);

/** Idiomatic predicate first words that need no is/has/can prefix. */
const ALLOWED_PREDICATES = new Set(['successful']);

/** Imperative command verbs that may return a result bool. */
const COMMAND_VERBS = new Set([
    'execute', 'run', 'handle', 'process', 'perform', 'persist', 'save',
    'store', 'write', 'read', 'load', 'fetch', 'delete', 'remove', 'forget',
    'flush', 'purge', 'clear', 'reset', 'refresh', 'sync', 'send', 'dispatch',
    'emit', 'apply', 'guard', 'validate', 'verify', 'authorize', 'ensure',
    'assert', 'register', 'boot', 'build', 'make', 'resolve', 'render',
    'compute', 'calculate', 'expose', 'parse', 'format', 'transform', 'toggle',
    'submit', 'confirm', 'copy', 'attempt', 'rotate',
]);

/** Method names that are a primitive type keyword: typed accessors, not predicates. */
const TYPE_ACCESSOR_NAMES = new Set(['boolean']);

/** Matches @imperative only at a docblock tag position, never inside prose. */
const IMPERATIVE_TAG = /^[ \t*]*@imperative(?![-\w])/im;

/** The leading camelCase word of a method name. */
function firstWord(name) {
    const match = /^[a-z]+/.exec(name);

    return match ? match[0] : '';
}

/**
 * Whether the name reads as a predicate: a copular/modal prefix, an idiomatic
 * predicate, or a verb ending in `s` (third-person) or `ed` (past tense).
 */
function isPredicate(name, prefixes, predicates) {
    const first = firstWord(name);

    return prefixes.has(first)
        || predicates.has(first)
        || first.endsWith('s')
        || first.endsWith('ed');
}

/** Whether the name is an imperative command verb, not a predicate. */
function isCommandVerb(name, verbs) {
    return verbs.has(firstWord(name));
}

/** Whether the name is an on* event-handler callback (onError, onUnauthorized). */
function isEventHandler(name) {
    return /^on[A-Z]/.test(name);
}

/**
 * Whether a return type resolves to boolean, ignoring a null, undefined or void
 * union member.
 */
function returnsBoolean(type) {
    if (type.flags & (ts.TypeFlags.Boolean | ts.TypeFlags.BooleanLiteral)) {
        return true;
    }

    if (type.isUnion()) {
        const parts = type.types.filter(
            part => (part.flags & (ts.TypeFlags.Null | ts.TypeFlags.Undefined | ts.TypeFlags.Void)) === 0,
        );

        return parts.length > 0 && parts.every(returnsBoolean);
    }

    return false;
}

/** Whether the node is an arrow or function expression. */
function isFunctionExpression(node) {
    return node != null
        && (node.type === 'ArrowFunctionExpression' || node.type === 'FunctionExpression');
}

/** The static name of a member key, or null when it is not statically named. */
function keyName(keyNode) {
    if (keyNode.type === 'Identifier' || keyNode.type === 'PrivateIdentifier') {
        return keyNode.name;
    }

    if (keyNode.type === 'Literal' && typeof keyNode.value === 'string') {
        return keyNode.value;
    }

    return null;
}

/** Peel `as`, `satisfies` and non-null wrappers off an expression. */
function unwrapExpression(node) {
    let current = node;

    while (
        current
        && (current.type === 'TSAsExpression'
            || current.type === 'TSSatisfiesExpression'
            || current.type === 'TSNonNullExpression')
    ) {
        current = current.expression;
    }

    return current;
}

/**
 * Whether an @imperative opt-out tag precedes the member, including a docblock
 * tucked between a decorator and the member name.
 */
function hasImperativeTag(sourceCode, docHost, nameNode) {
    const before = sourceCode.getCommentsBefore(docHost).at(-1);

    if (before?.type === 'Block' && IMPERATIVE_TAG.test(before.value)) {
        return true;
    }

    if (docHost.decorators?.length) {
        const inner = sourceCode.getCommentsBefore(nameNode).at(-1);

        return inner?.type === 'Block' && IMPERATIVE_TAG.test(inner.value);
    }

    return false;
}

/**
 * Whether the member is exempt: magic, an override, a type accessor, already a
 * predicate, a command or event handler, or tagged @imperative.
 */
function isExempt(state, nameNode, name, docHost) {
    if (name.startsWith('__') || TYPE_ACCESSOR_NAMES.has(name) || docHost.override === true) {
        return true;
    }

    if (isPredicate(name, state.prefixes, state.predicates)) {
        return true;
    }

    if (isCommandVerb(name, state.commandVerbs) || isEventHandler(name)) {
        return true;
    }

    return hasImperativeTag(state.sourceCode, docHost, nameNode);
}

/**
 * Report a non-exempt name whose awaited return type is boolean, skipping
 * type-predicate guards.
 */
function inspect(state, nameNode, name, fnNode, docHost) {
    const { checker, services, context } = state;

    if (isExempt(state, nameNode, name, docHost)) {
        return;
    }

    const signature = checker.getSignatureFromDeclaration(services.esTreeNodeToTSNodeMap.get(fnNode));

    if (!signature || checker.getTypePredicateOfSignature(signature)) {
        return;
    }

    const returnType = checker.getReturnTypeOfSignature(signature);

    if (!returnsBoolean(checker.getAwaitedType(returnType) ?? returnType)) {
        return;
    }

    context.report({ node: nameNode, messageId: 'notPredicate', data: { name } });
}

/** Inspect a function value bound to a statically named key. */
function inspectFunctionValue(state, keyNode, valueNode, docHost) {
    const name = keyName(keyNode);

    if (name !== null) {
        inspect(state, keyNode, name, valueNode, docHost);
    }
}

/**
 * Inspect a statically named method. A bodiless overload signature is skipped
 * unless abstract or ambient, so the implementation reports once.
 */
function inspectMember(state, node, allowEmptyBody) {
    if (node.computed || node.kind !== 'method') {
        return;
    }

    const name = keyName(node.key);

    if (name === null) {
        return;
    }

    if (!allowEmptyBody && node.value.body === null && !isAmbient(node, state.context.filename)) {
        return;
    }

    inspect(state, node.key, name, node, node);
}

/** The listeners for every named function, method and signature form. */
function buildListeners(state) {
    return {
        FunctionDeclaration(node) {
            if (node.id) {
                inspect(state, node.id, node.id.name, node, node);
            }
        },
        TSDeclareFunction(node) {
            if (node.id && isAmbient(node, state.context.filename)) {
                inspect(state, node.id, node.id.name, node, node);
            }
        },
        MethodDefinition(node) {
            inspectMember(state, node, false);
        },
        TSAbstractMethodDefinition(node) {
            inspectMember(state, node, true);
        },
        TSMethodSignature(node) {
            if (!node.computed && node.kind === 'method') {
                const name = keyName(node.key);

                if (name !== null) {
                    inspect(state, node.key, name, node, node);
                }
            }
        },
        PropertyDefinition(node) {
            const value = unwrapExpression(node.value);

            if (!node.computed && isFunctionExpression(value)) {
                inspectFunctionValue(state, node.key, value, node);
            }
        },
        Property(node) {
            const value = unwrapExpression(node.value);

            if (!node.computed && node.kind === 'init' && isFunctionExpression(value)) {
                inspectFunctionValue(state, node.key, value, node);
            }
        },
        VariableDeclarator(node) {
            const init = unwrapExpression(node.init);

            if (isFunctionExpression(init)) {
                inspectFunctionValue(state, node.id, init, node.parent);
            }
        },
    };
}

/**
 * Require a function or method returning boolean (awaited included) to read as
 * a predicate.
 *
 * Its first camelCase word must be an allowed prefix or predicate, or a verb
 * ending in `s` or `ed`. Command verbs, `on*` handlers, overrides and members
 * tagged `@imperative` are exempt. Needs type information; a no-op without it.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */
export default createRule({
    name: 'boolean-method-name',
    meta: {
        type: 'suggestion',
        docs: {
            description: 'Require an interrogative prefix on methods and functions that return boolean.',
        },
        schema: [{
            type: 'object',
            properties: {
                additionalPrefixes: { type: 'array', items: { type: 'string' } },
                additionalPredicates: { type: 'array', items: { type: 'string' } },
                additionalCommandVerbs: { type: 'array', items: { type: 'string' } },
            },
            additionalProperties: false,
        }],
        messages: {
            notPredicate: 'Boolean method "{{ name }}" should read as a predicate (is/has/can/...).',
        },
    },
    defaultOptions: [{ additionalPrefixes: [], additionalPredicates: [], additionalCommandVerbs: [] }],
    create(context, [options]) {
        const services = ESLintUtils.getParserServices(context, true);

        if (!services.program) {
            return {};
        }

        const state = {
            context,
            services,
            sourceCode: context.sourceCode,
            checker: services.program.getTypeChecker(),
            prefixes: new Set([...ALLOWED_PREFIXES, ...options.additionalPrefixes]),
            predicates: new Set([...ALLOWED_PREDICATES, ...options.additionalPredicates]),
            commandVerbs: new Set([...COMMAND_VERBS, ...options.additionalCommandVerbs]),
        };

        return buildListeners(state);
    },
});
