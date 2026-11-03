<?php

/**
 * @param  int  &$other
 */
function prefixesAnotherName(int $value): void {}

/**
 * @param  int  &$value
 * @param  int  &$value
 */
function repeatsAPrefixedTag(int $value): void {}

/**
 * @param  int  $thisValue
 */
function startsWithThis(int $thisValue): void {}

// @param  int  $wrong
function hasALineComment(int $value): void {}
