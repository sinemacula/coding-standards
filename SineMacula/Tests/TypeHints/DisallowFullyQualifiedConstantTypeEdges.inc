<?php

namespace App;

class Modifiers
{
    final public const \App\Thing SHORTEST = X::Y;

    public const App\Types\Partial PARTIAL = X::Y;

    public const \App\Chain\Of\Names DEEP = X::Y;

    public const \App\Multi ONE = X::Y, TWO = X::Y;
}

interface Contract
{
    const \App\Iface\Type IFACE_CONST = X::Y;
}

trait Helper
{
    public const \App\TraitNs\Type TRAIT_CONST = X::Y;
}

enum Suit
{
    public const \App\EnumNs\Type ENUM_CONST = X::Y;
}

class RelativeAndCompound
{
    public const namespace\App\Relative REL = X::Y;

    public const ?\Throwable MAYBE = X::Y;

    public const (\App\A&\App\B)|\App\C COMBINED = X::Y;

    public const Ordinary|\App\Deep\Type MIX = X::Y;
}
