<?php

// Flagged: the whole left operand is a concatenation.
$key = 'prefix|' . $id ?? $fallback;
$key = ('prefix|' . $id) ?? $fallback;
$key = $parts['a'] . format($b, $c) . $d->e() ?? $fallback;
$key = 'prefix|'
    . $id
    ?? $fallback;
call('prefix|' . $id ?? $fallback, $other);
$items = ['key' => 'prefix|' . $id ?? $fallback];
$first = $a ?? 'prefix|' . $id ?? $fallback;

function signature($id)
{
    return 'prefix|' . $id ?? 'anonymous';
}

$callback = fn ($id) => 'prefix|' . $id ?? 'anonymous';

// Not flagged: the coalesce applies to the nullable operand.
$key = 'prefix|' . ($id ?? $fallback);
$key = 'a' . ($b ?? 'c') . 'd';
$key = $id ?? $fallback;
$key = $map['prefix|' . $id] ?? $fallback;
$key = lookup('prefix|' . $id) ?? $fallback;
$key = $id ?? 'prefix|' . $fallback;
$key = $a . $b === $c ?? $fallback;
$key = $a || $b . $c ?? $fallback;
$key .= $id ?? $fallback;
$key = $flag ? 'prefix|' . $id : $id ?? $fallback;
$key = ['prefix|' . $id] ?? $fallback;
$key = lookup('prefix|'.$id)??$fallback;
