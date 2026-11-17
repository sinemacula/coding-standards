import { createRule, isTestClass, isTestPath } from './lib.js';

/** Whether a class member counts towards the method total. */
function isCountedMethod(member) {
    // Overload signatures have no body and count once, via the implementation.
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
 * Cap the number of methods declared directly on a single class, constructor
 * and accessors included. Test files and test classes are exempt.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
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
