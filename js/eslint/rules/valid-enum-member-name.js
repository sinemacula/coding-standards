import { createRule } from './lib.js';

/** The pattern a valid enum member name must match. */
const NAME_PATTERN = /^[A-Z][A-Z0-9_]*$/;

/**
 * Require enum members in SCREAMING_SNAKE_CASE. Object-literal `as const`
 * pseudo-enums are not checked.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
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
        return {
            TSEnumMember(node) {
                const id = node.id;
                // A string-literal name is checked by its value.
                const name = id.type === 'Identifier' ? id.name : String(id.value);

                if (!NAME_PATTERN.test(name)) {
                    context.report({ node: id, messageId: 'notUpperSnakeCase', data: { name } });
                }
            },
        };
    },
});
