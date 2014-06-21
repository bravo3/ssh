<?php
namespace Bravo3\SSH\Enum;

use Eloquent\Enumeration\AbstractEnumeration;

/**
 * Supported shells
 *
 * @method static ShellType UNKNOWN()
 * @method static ShellType SH()
 * @method static ShellType BASH()
 * @method static ShellType CSH()
 * @method static ShellType TCSH()
 * @method static ShellType ZSH()
 * @method static ShellType RZSH()
 * @method static ShellType ZSH5()
 * @method static ShellType KSH()
 * @method static ShellType KSH93()
 * @method static ShellType PDKSH()
 * @method static ShellType ASH()
 * @method static ShellType DASH()
 */
final class ShellType extends AbstractEnumeration
{
    const UNKNOWN = 'UNKNOWN';
    const SH      = 'sh';
    const BASH    = 'bash';
    const CSH     = 'csh';
    const TCSH    = 'tcsh';
    const ZSH     = 'zsh';
    const RZSH    = 'rzsh';
    const ZSH5    = 'zsh5';
    const KSH     = 'ksh';
    const KSH93   = 'ksh93';
    const PDKSH   = 'pdksh';
    const ASH     = 'ash';
    const DASH    = 'dash';
}
 