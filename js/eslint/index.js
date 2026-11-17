import jsdoc from 'eslint-plugin-jsdoc';
import tseslint from 'typescript-eslint';
import * as yamlParser from 'yaml-eslint-parser';
import plugin from './plugin.js';

const TS_FILES = ['**/*.ts', '**/*.tsx', '**/*.mts', '**/*.cts'];
const TS_AND_JS_FILES = [...TS_FILES, '**/*.js', '**/*.jsx', '**/*.mjs', '**/*.cjs'];
const YAML_FILES = ['**/*.yml', '**/*.yaml'];

/**
 * Base flat config: the custom rules that need no type information, so no
 * tsconfig. The type-aware layer is in ./type-checked.js.
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
            jsdoc,
        },
        languageOptions: {
            parser: tseslint.parser,
        },
        rules: {
            '@sinemacula/no-interface-prefix': 'error',
            '@sinemacula/require-readonly-public-property': 'error',
            '@sinemacula/valid-enum-member-name': 'error',

            '@typescript-eslint/no-explicit-any': 'error',

            // TypeScript only: in plain JavaScript the tag is the only place
            // the type is recorded.
            'jsdoc/no-types': 'error',
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
            '@sinemacula/require-file-description': 'error',
            '@sinemacula/align-doc-tags': 'error',
            '@sinemacula/single-line-property-doc': 'error',
            '@sinemacula/multiline-function-doc': 'error',
            '@sinemacula/comment-line-wrap': 'error',

            curly: ['error', 'all'],
            'max-lines-per-function': ['error', { max: 50, skipComments: true, skipBlankLines: true, IIFEs: true }],
            'max-depth': ['error', 4],

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
                // The autofix inserts an empty stub that fails
                // require-description.
                enableFixer: false,
            }],
            'jsdoc/require-description': 'error',
            'jsdoc/require-param-description': 'error',
            'jsdoc/require-returns-description': 'error',
            'jsdoc/lines-before-block': ['error', { lines: 1, ignoreSingleLines: false }],
        },
    },
    {
        // Only comment width; yamllint governs the rest of YAML.
        files: YAML_FILES,
        plugins: {
            '@sinemacula': plugin,
        },
        languageOptions: {
            parser: yamlParser,
        },
        rules: {
            '@sinemacula/comment-line-wrap': 'error',
        },
    },
    {
        // Specs are exempt from the size metrics but not from documentation;
        // an `it()` callback is a call argument, which require-jsdoc never
        // reaches.
        files: ['**/*.{test,spec}.{ts,tsx,mts,cts,js,jsx,mjs,cjs}', '**/__tests__/**', '**/tests/**', '**/test-support/**'],
        rules: {
            'max-lines-per-function': 'off',
            'max-depth': 'off',
        },
    },
];
