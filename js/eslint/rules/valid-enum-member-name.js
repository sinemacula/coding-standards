/**
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */

import { ESLintUtils } from '@typescript-eslint/utils';

const createRule = ESLintUtils.RuleCreator(
    name => `https://github.com/sinemacula/coding-standards#${name}`,
);

/** The pattern a valid enum member name must match. */
const NAME_PATTERN = /^[A-Z][A-Z0-9_]*$/;

/**
 * Enum member naming rule.
 *
 * Ensures every enum member is declared in SCREAMING_SNAKE_CASE, matching the
 * Sine Macula house convention for enum members. Only real `enum` declarations
 * are checked; object-literal "as const" pseudo-enums are out of scope.
 */
export default createRule({
    name: 'valid-enum-member-name',
    meta: {
        type: 'suggestion',
        docs: {
            description: 'Require enum members to be declared in SCREAMING_SNAKE_CASE.',
        },
        schema: [],
        messages: {
            notUpperSnakeCase: 'Enum member "{{ name }}" must be declared in SCREAMING_SNAKE_CASE.',
        },
    },
    defaultOptions: [],
    create(context) {
        // Only enum members are visited; a switch `case` is a SwitchCase node
        // and never reaches this visitor.
        return {
            TSEnumMember(node) {
                const id = node.id;
                // A member name is either a bare identifier or a string literal;
                // a string literal is checked by its resolved value, not the raw
                // source text.
                const name = id.type === 'Identifier' ? id.name : String(id.value);

                if (!NAME_PATTERN.test(name)) {
                    context.report({ node: id, messageId: 'notUpperSnakeCase', data: { name } });
                }
            },
        };
    },
});
