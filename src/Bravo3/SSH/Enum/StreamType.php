<?php
namespace Bravo3\SSH\Enum;

use Eloquent\Enumeration\AbstractEnumeration;

/**
 * @method static StreamType STDIO()
 * @method static StreamType STDERR()
 * @method static StreamType COMBINED()
 */
final class StreamType extends AbstractEnumeration
{
    const STDIO    = 0;
    const STDERR   = 1;
    const COMBINED = 2;
}
