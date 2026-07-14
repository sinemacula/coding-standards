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
]);

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

/**
 * Whether a return type resolves to boolean, ignoring a nullable `?bool`-style
 * null/undefined/void tail so an optional boolean still counts. Promise wrappers
 * are unwrapped before this is called, so an awaited boolean counts too.
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

/** Whether the node is a function expression carrying an inspectable signature. */
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

/** Peel `as`/`satisfies`/non-null wrappers off an expression to reach the value. */
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
 * Report the name when it neither reads as a predicate nor is exempt and the
 * resolved (awaited) return type is boolean. Type-predicate guards are predicates
 * by structure and left alone.
 */
function inspect(state, nameNode, name, fnNode, docHost) {
    const { checker, services, context, sourceCode } = state;

    if (
        name.startsWith('__')
        || isPredicate(name, state.prefixes, state.predicates)
        || isCommandVerb(name, state.commandVerbs)
        || hasImperativeTag(sourceCode, docHost, nameNode)
    ) {
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

/**
 * Guard a statically named function value (arrow field, object method or const
 * binding) down to a named key, then inspect it.
 */
function inspectFunctionValue(state, keyNode, valueNode, docHost) {
    const name = keyName(keyNode);

    if (name !== null) {
        inspect(state, keyNode, name, valueNode, docHost);
    }
}

/**
 * Guard a class member down to a statically named, non-accessor method, then
 * inspect it. A bodiless member is dropped as an overload signature so the
 * implementation reports once, unless it is abstract or ambient, where the
 * bodiless declaration is the real thing to check.
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

/**
 * The visitor: inspect each named function, method, signature and function-valued
 * member for a boolean return that does not read as a predicate.
 */
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
 * Boolean method name rule.
 *
 * A function, method, arrow-bound class field, object method or interface/type
 * signature returning boolean should read as a predicate. A name is accepted when
 * its first camelCase word is a copular or modal prefix (is, has, can, ...), an
 * idiomatic predicate from ALLOWED_PREDICATES (e.g. successful), or a verb ending
 * in `s` (third-person: permits, passes) or `ed` (past tense: succeeded, failed,
 * expired). An imperative command verb (execute, persist, guard, ...) that returns
 * a result bool is exempt via COMMAND_VERBS. A member may also opt out with an
 * @imperative docblock tag. Accessors, the constructor, computed names, magic
 * names and type-predicate guards (x is T) are exempt. The return type is resolved
 * from type information - inferred booleans and awaited Promise<boolean> included -
 * so the rule degrades to a no-op when no type information is available. The
 * accepted vocabulary can be widened per consumer through the rule options.
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

        // Without a type-checker program the return type can't be resolved, so
        // the rule cannot decide anything; degrade to a no-op rather than throw.
        if (!services.program) {
            return {};
        }

        // Merge consumer additions onto the defaults so a downstream ruleset can
        // widen the accepted vocabulary without losing the built-in words.
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
