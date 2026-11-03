<?php

namespace App;

abstract class Fixture
{
    // Flagged: a parameter with no tag, a tag naming no variable or another one.

    /**
     * @param  int  $first
     */
    public function missesATag(int $first, int $second): void {}

    /**
     * @param  int  $wrong
     */
    public function misnamesATag(int $right): void {}

    /**
     * @param  int  $Value
     */
    public function misnamesTheCase(int $value): void {}

    /**
     * @param  int
     */
    public function namesNoVariable(int $value): void {}

    /**
     * @param  array{
     *     name: string
     * }
     */
    public function namesNoVariableAcrossLines(array $row): void {}

    /**
     * @param  array{
     *     name: string
     * }  $wrong
     */
    public function misnamesATagAcrossLines(array $row): void {}

    /**
     * @param  int  $value
     * @param  int  $surplus
     * @param  array{
     *     name: string
     * }  $another
     */
    public function carriesSurplusTags(int $value): void {}

    /**
     * @param  array{
     *     name: string
     * }  &$row
     */
    public function prefixesAnAmpersand(array $row): void {}

    /**
     * @param  int  $values
     */
    public function dropsTheEllipsis(int ...$values): void {}

    /**
     * @param  int  $second
     * @param  int  $first
     */
    public function swapsTheOrder(int $first, int $second): void {}

    /**
     * @param  $value
     */
    public function omitsTheType(int $value): void {}

    /**
     * {@inheritDoc}
     */
    public function inheritsItsComment(int $value): void {}

    /**
     * @param  callable(int $a,
     *     string $b): void
     */
    public function namesOnlyTheCallableParameters(callable $callback): void {}

    /**
     * @return void
     */
    abstract public function declaresAnAbstract(int $value): void;

    /**
     * @param
     */
    public function leavesTheTagBare(int $value): void {}

    // Not flagged: every parameter named in order, however the type is written.

    /**
     * @param  int  $value
     * @param  string  &$buffer
     * @param  int  ...$rest
     */
    public function namesEachOnOneLine(int $value, string &$buffer, int ...$rest): void {}

    /**
     * @param  array{
     *     name: string,
     *     value: int
     * }  $data
     * @param  list<array{
     *     name: string
     * }> $rows
     * @param  array<string, array<int,
     *     list<string>>>  $groups
     */
    public function namesEachAcrossLines(array $data, array $rows, array $groups): void {}

    /**
     * @param  callable(
     *     int $value,
     *     string $label
     * ): bool  $filter
     * @param  callable(int $value, string $label):
     *     bool  $callback
     * @param  callable(int $value): void  $listener
     */
    public function namesCallablesThatNameTheirOwn(callable $filter, callable $callback, callable $listener): void {}

    /**
     * @param  array{
     *     name: string
     * }|null  $row
     * @param  int
     *     |string  $key
     */
    public function namesUnionsAcrossLines(?array $row, int|string $key): void {}

    /**
     * @param  array{
     *     name: string
     * } $row
     * @param  int  &...$refs
     */
    #[\Deprecated]
    final public static function namesTheRestAfterAnAttribute(array $row, int &...$refs): void {}

    /**
     * @param  $this  $other
     */
    public function takesItsOwnType(self $other): void {}

    /**
     * @param  int  $value
     * @param  int  $more,...
     */
    public function documentsTheLegacyVariadic(int $value): void {}

    /**
     * @return void
     */
    public function takesNothing(): void {}

    public function hasNoComment(int $value): void {}

    public function closesOverIts(): void
    {
        /**
         * @param  int  $wrong
         */
        $closure = function (int $right): void {};

        /** @param  int  $wrong */
        $arrow = fn (int $right): int => $right;
    }
}

final class Promoted
{
    /**
     * @param  string  $name
     */
    public function __construct(

        public readonly string $name,

        private readonly int $count,
    ) {}
}

interface Contract
{
    /**
     * @return void
     */
    public function declares(int $value): void;
}
