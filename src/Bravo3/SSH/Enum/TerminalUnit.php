<?php
namespace Bravo3\SSH\Enum;

use Eloquent\Enumeration\AbstractEnumeration;

/**
 * @method static TerminalUnit CHARACTERS()
 * @method static TerminalUnit PIXELS()
 */
final class TerminalUnit extends AbstractEnumeration
{
    const CHARACTERS = SSH2_TERM_UNIT_CHARS;
    const PIXELS     = SSH2_TERM_UNIT_PIXELS;
}