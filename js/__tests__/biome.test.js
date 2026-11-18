/**
 * Tests for the shared Biome config's filename and identifier conventions, run
 * through the Biome version the README has consumers pin.
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

/** The diagnostics of one rule that Biome reports for a file with the given path and source. */
function diagnostics(rule, path, source) {
    mkdirSync(dirname(join(consumer, path)), { recursive: true });
    writeFileSync(join(consumer, path), source);

    // The rules only warn, so Biome exits cleanly either way.
    const output = execFileSync(BIOME, ['lint', '--reporter=rdjson', path], { cwd: consumer, encoding: 'utf8' });

    return JSON.parse(output).diagnostics.filter(diagnostic => diagnostic.code.value === `lint/style/${rule}`);
}

/** The filename diagnostics Biome reports for a file at the given path. */
function filenameDiagnostics(path) {
    return diagnostics('useFilenamingConvention', path, 'export const value = 1;\n');
}

/** The identifier naming diagnostics Biome reports for the given source. */
function namingDiagnostics(source) {
    return diagnostics('useNamingConvention', 'src/naming.ts', source);
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

describe('identifiers', () => {
    it('accepts consecutive capitals', () => {
        expect(namingDiagnostics("import UIButton from './ui-button.js';\n\nexport { UIButton };\n")).toEqual([]);
        expect(namingDiagnostics('export function parseJSON() {}\n')).toEqual([]);
    });

    it('still rejects other casings', () => {
        expect(namingDiagnostics('export const user_name = 1;\n')).toHaveLength(1);
    });
});
