import { createRule } from './lib.js';

/** Disallowed "I" prefix: a capital I directly followed by another uppercase letter. */
const PREFIX_PATTERN = /^I[A-Z]/;

/** A global or string-named module block augments types we do not own. */
const isExternalAugmentation = node =>
    node.type === 'TSModuleDeclaration'
    && (node.kind === 'global' || node.id.type === 'Literal');

/**
 * Forbids the Hungarian "I" prefix on interface and type-alias names
 * (IUserRepository), which adds nothing over the language's own type system.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */
export default createRule({
    name: 'no-interface-prefix',
    meta: {
        type: 'problem',
        docs: {
            description: 'Disallow the "I" prefix on interface and type-alias names.',
        },
        schema: [],
        messages: {
            prefixed: '{{ kind }} "{{ name }}" must not use an "I" prefix.',
        },
    },
    defaultOptions: [],
    create(context) {
        const { sourceCode } = context;

        /** Report a declaration whose name carries the disallowed "I" prefix. */
        const check = (node, kind) => {
            const name = node.id.name;

            if (!PREFIX_PATTERN.test(name)) {
                return;
            }

            // Augmenting an external module or the global scope cannot rename it.
            if (sourceCode.getAncestors(node).some(isExternalAugmentation)) {
                return;
            }

            context.report({ node: node.id, messageId: 'prefixed', data: { kind, name } });
        };

        return {
            TSInterfaceDeclaration: node => check(node, 'Interface'),
            TSTypeAliasDeclaration: node => check(node, 'Type'),
        };
    },
});
