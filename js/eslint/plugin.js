import booleanMethodName from './rules/boolean-method-name.js';
import noInterfacePrefix from './rules/no-interface-prefix.js';
import noMutableStatic from './rules/no-mutable-static.js';
import requireReadonlyPublicProperty from './rules/require-readonly-public-property.js';
import validEnumMemberName from './rules/valid-enum-member-name.js';

/**
 * The @sinemacula ESLint plugin.
 *
 * Bundles this package's custom structural rules. Each rule is registered in
 * the map below; the flat configs in index.js and type-checked.js are what
 * switch them on.
 */
export default {
    meta: {
        name: '@sinemacula/coding-standards',
    },
    rules: {
        'no-interface-prefix': noInterfacePrefix,
        'boolean-method-name': booleanMethodName,
        'require-readonly-public-property': requireReadonlyPublicProperty,
        'valid-enum-member-name': validEnumMemberName,
        'no-mutable-static': noMutableStatic,
    },
};
