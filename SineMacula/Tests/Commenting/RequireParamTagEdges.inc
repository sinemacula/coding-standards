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

/**
 * @see prefixesAnotherName()
 * @param  int  $wrong
 */
function namesAfterAnotherTag(int $value): void {}

/**
 * @param  int  $total
 */
function capitalisesTheParameter(int $Total): void {}
