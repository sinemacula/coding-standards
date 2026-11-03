<?php

/**
 * Summary prose that mentions @param and @throws stays.
 *
 * A long description stays too.
 *
 * @param  array{
 *     id: int,
 *     name: string,
 * }  $row  The row, its type spread over lines.
 * @param  callable(int $value): void  $callback The callback.
 * @param  $untyped An untyped parameter.
 * @param  int  &...$refs By reference and variadic.
 * @param  int
 * @param  int  $bare
 * @return int The count.
 *
 * @throws \Foo<int, string> When the type has a space.
 * @throws \Bar|\Baz When either fails.
 * @throws
 * @see Something else entirely.
 */
function edges(array $row, callable $callback, $untyped, int &...$refs): int
{
    return 0;
}

/** @param  int  $value The value. */
$closure = static function (int $value): void {
};

/** @throws \RuntimeException When it fails.*/
$thrower = static fn (): never => throw new \RuntimeException();

/** @var int $count The count. */
$count = 1;

/**
 * @param  int  $value The value.
 *     More about it.
 *
 *     And more after a blank line.
 *
 * @return void
 */
function spaced(int $value): void
{
}

/**
 * @throws \RuntimeException
 *     When it fails. */
function closing(): void
{
}

/** @param  int  $value */
$bare = static function (int $value): void {
};

/**
 * @param  \Closure(): $this  $callback
 * @param  $this  $other
 * @param  $this|null  $self The self.
 * @param  int  $thisValue The value.
 * @return void
 *
 * @throws \FooException | \BarException When either fails.
 * @throws \FooException | \BarException
 */
function selfish(\Closure $callback, $other, $self, int $thisValue): void
{
}
