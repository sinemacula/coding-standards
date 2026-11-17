/**
 * Tests for the Vue layer's component filename convention.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */

import { Linter } from 'eslint';
import { describe, expect, it } from 'vitest';
import config from '../vue.js';

const linter = new Linter({ configType: 'flat' });

const COMPONENT = [
    '<template>',
    '    <button type="button">',
    '        <slot />',
    '    </button>',
    '</template>',
    '',
].join('\n');

/** The distinct rules that reported on the component linted as the given filename. */
function report(filename) {
    return [...new Set(linter.verify(COMPONENT, config, filename).map(message => message.ruleId))];
}

describe('component filenames', () => {
    it('accepts PascalCase, including consecutive capitals', () => {
        for (const filename of ['src/components/UserCard/UserCard.vue', 'src/components/UIButton.vue']) {
            expect(report(filename)).toEqual([]);
        }
    });

    it('rejects every other casing', () => {
        for (const filename of ['user-card.vue', 'userCard.vue', 'user_card.vue']) {
            expect(report(filename)).toContain('check-file/filename-naming-convention');
        }
    });
});
