import jsdoc from 'eslint-plugin-jsdoc';
import tseslint from 'typescript-eslint';
import plugin from './plugin.js';

const TS_FILES = ['**/*.ts', '**/*.tsx', '**/*.mts', '**/*.cts'];
const TS_AND_JS_FILES = [...TS_FILES, '**/*.js', '**/*.jsx', '**/*.mjs', '**/*.cjs'];

/**
 * Base flat config: the AST-only custom rules that need no type information.
 *
 * Requires no tsconfig, so it stays cheap. The typescript-eslint parser
 * resolves TypeScript syntax. The interface, readonly-property and enum rules
 * target TypeScript-only constructs; no-mutable-static also applies to plain
 * JavaScript (exported let/var, mutable static fields), so it runs across both.
 * The opt-in type-aware layer lives in ./type-checked.js.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */
export default [
    {
        files: TS_FILES,
        plugins: {
            '@sinemacula': plugin,
            '@typescript-eslint': tseslint.plugin,
        },
        languageOptions: {
            parser: tseslint.parser,
        },
        rules: {
            '@sinemacula/no-interface-prefix': 'error',
            '@sinemacula/require-readonly-public-property': 'error',
            '@sinemacula/valid-enum-member-name': 'error',

            '@typescript-eslint/no-explicit-any': 'error',
        },
    },
    {
        files: TS_AND_JS_FILES,
        plugins: {
            '@sinemacula': plugin,
            jsdoc,
        },
        languageOptions: {
            parser: tseslint.parser,
        },
        rules: {
            '@sinemacula/no-mutable-static': 'error',
            '@sinemacula/max-methods-per-class': 'error',
            '@sinemacula/no-base-error': 'error',
            '@sinemacula/require-copyright': 'error',
            '@sinemacula/align-doc-tags': 'error',
            '@sinemacula/pad-block-start': 'error',
            '@sinemacula/single-line-property-doc': 'error',
            '@sinemacula/multiline-function-doc': 'error',

            'max-lines-per-function': ['error', { max: 50, skipComments: true, skipBlankLines: true, IIFEs: true }],
            'max-depth': ['error', 4],

            // Every declared function, method, class, assigned arrow and
            // property carries a documentation comment describing intent, so a
            // reader meets each member's purpose before its type. Interface
            // members and class fields are held to the same bar as methods.
            // Types live in the signature, so a tag never annotates one: the
            // @param and @returns tags themselves are welcome, only their type
            // forms are not. Each block stands off from the code above it,
            // single-line blocks included.
            'jsdoc/require-jsdoc': ['error', {
                require: {
                    ClassDeclaration: true,
                    ClassExpression: true,
                    FunctionDeclaration: true,
                    MethodDefinition: true,
                },
                contexts: [
                    'VariableDeclarator > ArrowFunctionExpression',
                    'VariableDeclarator > FunctionExpression',
                    'TSPropertySignature',
                    'TSMethodSignature',
                    'PropertyDefinition',
                ],
                checkConstructors: false,
                // Report only; the autofix inserts an empty stub that then
                // fails require-description, so a blind fix would scatter
                // hollow comments rather than resolve the finding.
                enableFixer: false,
            }],
            'jsdoc/require-description': 'error',
            'jsdoc/no-types': 'error',
            'jsdoc/require-param-description': 'error',
            'jsdoc/require-returns-description': 'error',
            'jsdoc/lines-before-block': ['error', { lines: 1, ignoreSingleLines: false }],
        },
    },
    {
        files: ['**/*.{test,spec}.{ts,tsx,mts,cts,js,jsx,mjs,cjs}', '**/__tests__/**', '**/tests/**'],
        rules: {
            'max-lines-per-function': 'off',
            'max-depth': 'off',
            'jsdoc/require-jsdoc': 'off',
        },
    },
];
