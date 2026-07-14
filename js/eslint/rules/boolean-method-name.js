import * as ts from 'typescript';
import { ESLintUtils } from '@typescript-eslint/utils';

const createRule = ESLintUtils.RuleCreator(
    name => `https://github.com/sinemacula/coding-standards#${name}`,
);

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

/**
 * The leading camelCase word of a method name.
 *
 * @param {string} name
 * @returns {string}
 */
function firstWord(name) {
    const match = /^[a-z]+/.exec(name);

    return match ? match[0] : '';
}

/**
 * Whether the name reads as a predicate: a copular/modal prefix, an idiomatic
 * predicate, or a verb ending in `s` (third-person) or `ed` (past tense).
 *
 * @param {string} name
 * @returns {boolean}
 */
function isPredicate(name) {
    const first = firstWord(name);

    return ALLOWED_PREFIXES.has(first)
        || ALLOWED_PREDICATES.has(first)
        || first.endsWith('s')
        || first.endsWith('ed');
}

/**
 * Whether the name is an imperative command verb, not a predicate.
 *
 * @param {string} name
 * @returns {boolean}
 */
function isCommandVerb(name) {
    return COMMAND_VERBS.has(firstWord(name));
}

/**
 * Whether a return type resolves to boolean, ignoring a nullable `?bool`-style
 * null/undefined/void tail so an optional boolean still counts. Promise wrappers
 * are unwrapped before this is called, so an awaited boolean counts too.
 *
 * @param {import('typescript').Type} type
 * @returns {boolean}
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

/**
 * Whether the node is a function expression carrying an inspectable signature.
 *
 * @param {import('@typescript-eslint/utils').TSESTree.Node} node
 * @returns {boolean}
 */
function isFunctionExpression(node) {
    return node != null
        && (node.type === 'ArrowFunctionExpression' || node.type === 'FunctionExpression');
}

/**
 * Whether a node sits in an ambient context (a `declare` block or a .d.ts file),
 * where a bodiless declaration is the real thing to check, not an overload.
 *
 * @param {import('@typescript-eslint/utils').TSESTree.Node} node
 * @param {string} filename
 * @returns {boolean}
 */
function isAmbient(node, filename) {
    if (/\.d\.[cm]?ts$/.test(filename)) {
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
 * so the rule degrades to a no-op when no type information is available.
 */
export default createRule({
    name: 'boolean-method-name',
    meta: {
        type: 'suggestion',
        docs: {
            description: 'Require an interrogative prefix on methods and functions that return boolean.',
        },
        schema: [],
        messages: {
            notPredicate: 'Boolean method "{{ name }}" should read as a predicate (is/has/can/...).',
        },
    },
    defaultOptions: [],
    create(context) {
        const services = ESLintUtils.getParserServices(context, true);

        // Without a type-checker program the return type can't be resolved, so
        // the rule cannot decide anything; degrade to a no-op rather than throw.
        if (!services.program) {
            return {};
        }

        const checker = services.program.getTypeChecker();

        /**
         * Whether an @imperative opt-out tag precedes the member, including a
         * docblock tucked between a decorator and the member name.
         *
         * @param {import('@typescript-eslint/utils').TSESTree.Node} docHost
         * @param {import('@typescript-eslint/utils').TSESTree.Node} nameNode
         * @returns {boolean}
         */
        function hasImperativeTag(docHost, nameNode) {
            const before = context.sourceCode.getCommentsBefore(docHost).at(-1);

            if (before?.type === 'Block' && IMPERATIVE_TAG.test(before.value)) {
                return true;
            }

            if (docHost.decorators?.length) {
                const inner = context.sourceCode.getCommentsBefore(nameNode).at(-1);

                return inner?.type === 'Block' && IMPERATIVE_TAG.test(inner.value);
            }

            return false;
        }

        /**
         * Report the name when it neither reads as a predicate nor is exempt and
         * the resolved (awaited) return type is boolean. Type-predicate guards are
         * predicates by structure and left alone.
         *
         * @param {import('@typescript-eslint/utils').TSESTree.Node} nameNode
         * @param {string} name
         * @param {import('@typescript-eslint/utils').TSESTree.Node} fnNode
         * @param {import('@typescript-eslint/utils').TSESTree.Node} docHost
         * @returns {void}
         */
        function inspect(nameNode, name, fnNode, docHost) {
            if (
                name.startsWith('__')
                || isPredicate(name)
                || isCommandVerb(name)
                || hasImperativeTag(docHost, nameNode)
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
         * Guard a statically named function value (arrow field, object method or
         * const binding) down to an identifier key, then inspect it.
         *
         * @param {import('@typescript-eslint/utils').TSESTree.Node} keyNode
         * @param {import('@typescript-eslint/utils').TSESTree.Node} valueNode
         * @param {import('@typescript-eslint/utils').TSESTree.Node} docHost
         * @returns {void}
         */
        function inspectFunctionValue(keyNode, valueNode, docHost) {
            if (keyNode.type !== 'Identifier' && keyNode.type !== 'PrivateIdentifier') {
                return;
            }

            inspect(keyNode, keyNode.name, valueNode, docHost);
        }

        /**
         * Guard a class member down to a statically named, non-accessor method,
         * then inspect it. A bodiless member is dropped as an overload signature
         * so the implementation reports once, unless it is abstract or ambient,
         * where the bodiless declaration is the real thing to check.
         *
         * @param {import('@typescript-eslint/utils').TSESTree.MethodDefinition
         *   | import('@typescript-eslint/utils').TSESTree.TSAbstractMethodDefinition} node
         * @param {boolean} allowEmptyBody
         * @returns {void}
         */
        function inspectMember(node, allowEmptyBody) {
            if (node.computed || node.kind !== 'method') {
                return;
            }

            if (node.key.type !== 'Identifier' && node.key.type !== 'PrivateIdentifier') {
                return;
            }

            if (!allowEmptyBody && node.value.body === null && !isAmbient(node, context.filename)) {
                return;
            }

            inspect(node.key, node.key.name, node, node);
        }

        return {
            FunctionDeclaration(node) {
                if (node.id) {
                    inspect(node.id, node.id.name, node, node);
                }
            },
            TSDeclareFunction(node) {
                if (node.id && isAmbient(node, context.filename)) {
                    inspect(node.id, node.id.name, node, node);
                }
            },
            MethodDefinition(node) {
                inspectMember(node, false);
            },
            TSAbstractMethodDefinition(node) {
                inspectMember(node, true);
            },
            TSMethodSignature(node) {
                if (node.computed || node.kind !== 'method' || node.key.type !== 'Identifier') {
                    return;
                }

                inspect(node.key, node.key.name, node, node);
            },
            PropertyDefinition(node) {
                if (!node.computed && isFunctionExpression(node.value)) {
                    inspectFunctionValue(node.key, node.value, node);
                }
            },
            Property(node) {
                if (!node.computed && node.kind === 'init' && isFunctionExpression(node.value)) {
                    inspectFunctionValue(node.key, node.value, node);
                }
            },
            VariableDeclarator(node) {
                if (isFunctionExpression(node.init)) {
                    inspectFunctionValue(node.id, node.init, node.parent);
                }
            },
        };
    },
});
