/**
 * Tests for the shared Biome config's filename convention, run through the
 * Biome version the README has consumers pin.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */

import { execFileSync } from 'node:child_process';
import { copyFileSync, mkdirSync, mkdtempSync, rmSync, writeFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';
import { afterAll, beforeAll, describe, expect, it } from 'vitest';

const ROOT = join(dirname(fileURLToPath(import.meta.url)), '../..');
const BIOME = join(ROOT, 'node_modules/.bin/biome');

let consumer;

beforeAll(() => {
    consumer = mkdtempSync(join(tmpdir(), 'coding-standards-biome-'));
    copyFileSync(join(ROOT, 'js/biome.shared.json'), join(consumer, 'biome.shared.json'));
    writeFileSync(join(consumer, 'biome.json'), JSON.stringify({ root: true, extends: ['./biome.shared.json'] }));
});

afterAll(() => {
    rmSync(consumer, { recursive: true, force: true });
});

/** The filename diagnostics Biome reports for a file at the given path. */
function filenameDiagnostics(path) {
    mkdirSync(dirname(join(consumer, path)), { recursive: true });
    writeFileSync(join(consumer, path), 'export const value = 1;\n');

    // The rule only warns, so Biome exits cleanly either way.
    const output = execFileSync(BIOME, ['lint', '--reporter=rdjson', path], { cwd: consumer, encoding: 'utf8' });

    return JSON.parse(output).diagnostics.filter(
        diagnostic => diagnostic.code.value === 'lint/style/useFilenamingConvention',
    );
}

describe('filenames', () => {
    it('accepts kebab-case anywhere', () => {
        for (const path of ['src/lib/date-format.ts', 'src/components/UserCard/use-hover.ts']) {
            expect(filenameDiagnostics(path)).toEqual([]);
        }
    });

    it('accepts PascalCase under a components directory, including consecutive capitals', () => {
        for (const path of [
            'src/components/UserCard/UserCard.test.ts',
            'src/components/UserCard/UserCard.stories.ts',
            'src/components/UIButton/UIButton.test.ts',
            'src/components/AccountCard.tsx',
        ]) {
            expect(filenameDiagnostics(path)).toEqual([]);
        }
    });

    it('rejects PascalCase outside a components directory', () => {
        expect(filenameDiagnostics('src/lib/DateFormat.ts')).toHaveLength(1);
    });

    it('rejects other casings under a components directory', () => {
        for (const path of ['src/components/UserCard/useHover.ts', 'src/components/UserCard/User_Card.ts']) {
            expect(filenameDiagnostics(path)).toHaveLength(1);
        }
    });
});
