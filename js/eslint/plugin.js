/**
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */

import booleanMethodName from './rules/boolean-method-name.js';
import maxMethodsPerClass from './rules/max-methods-per-class.js';
import noBaseError from './rules/no-base-error.js';
import noInterfacePrefix from './rules/no-interface-prefix.js';
import noMutableStatic from './rules/no-mutable-static.js';
import requireCopyright from './rules/require-copyright.js';
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
        'max-methods-per-class': maxMethodsPerClass,
        'no-base-error': noBaseError,
        'require-copyright': requireCopyright,
    },
};
