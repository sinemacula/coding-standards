<?php

namespace App;

final class Service
{
}

abstract class Base
{
}

class Plain
{
}

/**
 * A class designed for extension.
 *
 * @inheritable
 */
class Extendable
{
}

final readonly class Value
{
}

/**
 * Documented but still concrete.
 */
class Documented
{
}

/**
 * Designed for extension, behind an attribute.
 *
 * @inheritable
 */
#[\AllowDynamicProperties]
class ExtendableWithAttribute
{
}

#[\AllowDynamicProperties]
class PlainWithAttribute
{
}
