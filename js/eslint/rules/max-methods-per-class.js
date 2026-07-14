/**
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */

import { createRule, isTestClass, isTestPath } from './lib.js';

/** Whether a class member counts towards the method total. */
function isCountedMethod(member) {
    // An overload signature shares its name with the implementation below it, so it
    // carries no body and is not counted a second time.
    return member.type === 'MethodDefinition'
        && member.value.type !== 'TSEmptyBodyFunctionExpression';
}

/** The number of methods declared directly on the class body. */
function countMethods(node) {
    let count = 0;

    for (const member of node.body.body) {
        if (isCountedMethod(member)) {
            count++;
        }
    }

    return count;
}

/**
 * Cap the number of methods declared directly on a single class.
 *
 * A class that grows past the limit is doing too many jobs and should be split.
 * Every method declared on the class body counts, including the constructor,
 * static methods and get/set accessors; a method spread across overload
 * signatures counts once, through its implementation. Methods on a nested class
 * belong to that class, not the one enclosing it. Test code legitimately gathers
 * many methods on one fixture, so a test file or test class is exempt.
 */
export default createRule({
    name: 'max-methods-per-class',
    meta: {
        type: 'suggestion',
        docs: {
            description: 'Limit the number of methods declared on a single class.',
        },
        schema: [
            {
                type: 'object',
                properties: {
                    max: { type: 'integer', minimum: 0 },
                },
                additionalProperties: false,
            },
        ],
        messages: {
            tooMany: 'Class declares {{ count }} methods; the maximum is {{ max }}.',
        },
    },
    defaultOptions: [{ max: 20 }],
    create(context, [options]) {
        const { max }        = options;
        const { sourceCode } = context;

        if (isTestPath(context.filename)) {
            return {};
        }

        /** Flag a class whose method count runs past the configured maximum. */
        const inspect = node => {
            if (isTestClass(node)) {
                return;
            }

            const count = countMethods(node);

            if (count <= max) {
                return;
            }

            const target = node.id ?? sourceCode.getFirstToken(node, { filter: token => token.value === 'class' });

            context.report({
                loc: target.loc,
                messageId: 'tooMany',
                data: { count, max },
            });
        };

        return {
            ClassDeclaration: inspect,
            ClassExpression: inspect,
        };
    },
});
