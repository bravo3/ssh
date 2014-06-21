<?php
namespace Bravo3\SSH\Enum;

use Eloquent\Enumeration\AbstractEnumeration;

/**
 * @method static TerminalType XTERM()
 * @method static TerminalType VANILLA()
 * @method static TerminalType VT102()
 */
final class TerminalType extends AbstractEnumeration
{
    const XTERM   = 'xterm';
    const VANILLA = 'vanilla';
    const VT102   = 'vt102';
} 