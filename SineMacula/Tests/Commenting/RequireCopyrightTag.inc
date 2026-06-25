<?php

namespace App;

/**
 * A documented service.
 *
 * @author Ben Carey
 * @copyright 2026 Sine Macula Limited
 */
final class Documented
{
}

/**
 * Missing the copyright tag.
 *
 * @author Ben Carey
 */
final class NoCopyright
{
}

final class NoDocblock
{
}

/**
 * A documented service, behind an attribute.
 *
 * @author Ben Carey
 * @copyright 2026 Sine Macula Limited
 */
#[\AllowDynamicProperties]
final class DocumentedWithAttribute
{
}

/**
 * Missing the copyright tag, behind an attribute.
 *
 * @author Ben Carey
 */
#[\AllowDynamicProperties]
final class NoCopyrightWithAttribute
{
}
