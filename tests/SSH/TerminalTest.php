<?php
namespace SSH;

use Bravo3\SSH\Enum\TerminalType;
use Bravo3\SSH\Enum\TerminalUnit;
use Bravo3\SSH\Terminal;

class TerminalTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @small
     */
    public function testProperties()
    {
        $terminal = new Terminal();
        $this->assertEquals(TerminalType::VT102, $terminal->getTerminalType());
        $this->assertEquals(TerminalUnit::CHARACTERS, $terminal->getDimensionUnitType());
    }

}
 