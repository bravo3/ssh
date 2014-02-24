<?php
namespace NovaTek\SSH;

use NovaTek\SSH\Enum\TerminalType;
use NovaTek\SSH\Enum\TerminalUnit;

/**
 * Specifications for a virtual terminal
 */
class Terminal
{

    /**
     * @var string
     */
    protected $terminal_type;

    /**
     * @var int
     */
    protected $height;

    /**
     * @var int
     */
    protected $width;

    /**
     * @var array
     */
    protected $env;

    /**
     * @var int
     */
    protected $dimension_unit_type;

    /**
     * Create a new terminal for command execution or shells
     *
     * @param int    $width
     * @param int    $height
     * @param int    $dimension_unit_type Pixels or characters for height/width
     * @param string $terminal_type       Required only for interactive shells
     * @param array  $env                 Key/value array of environment variables
     */
    function __construct(
        $width = 80,
        $height = 25,
        $dimension_unit_type = TerminalUnit::CHARACTERS,
        $terminal_type = TerminalType::XTERM,
        $env = []
    ) {
        $this->width               = $width;
        $this->height              = $height;
        $this->dimension_unit_type = $dimension_unit_type;
        $this->terminal_type       = $terminal_type;
        $this->env                 = $env;
    }

    /**
     * Get environment variables passed to the terminal
     *
     * @return array
     */
    public function getEnv()
    {
        return $this->env;
    }

    /**
     * Get terminal height
     *
     * @return int
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * Get the unit type for the terminal height/width
     *
     * @return int
     */
    public function getDimensionUnitType()
    {
        return $this->dimension_unit_type;
    }

    /**
     * Get the type of terminal
     *
     * @return string
     */
    public function getTerminalType()
    {
        return $this->terminal_type;
    }

    /**
     * Get terminal width
     *
     * @return int
     */
    public function getWidth()
    {
        return $this->width;
    }


}