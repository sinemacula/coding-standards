<?php

/**
 * @param  '('|'['|'{'  $opener
 * @param  '<'|'<='  $operator
 * @param  "}"|'\''  $closer
 */
function quotesItsBrackets(string $opener, string $operator, string $closer): void {}

/**
 * @param  '['  $wrong
 */
function quotesABracketAndMisnames(string $open): void {}

/**
 * @param  int
 *     $count
 */
function namesTheVariableOnTheNextLine(int $count): void {}

/**
 * @param  int
 *     see $other
 */
function describesAnotherVariable(int $count): void {}

/**
 * @param  callable(int $value):
 *     bool  $callback
 */
function returnsOnTheNextLine(callable $callback): void {}

/**
 * @param  int
 *     &string  $key
 * @param  int  don't $value
 */
function intersectsAcrossLines(int $key, int $value): void {}
