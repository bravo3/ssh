<?php
namespace NovaTek\SSH;

use NovaTek\SSH\Exceptions\NotAuthenticatedException;
use NovaTek\SSH\Exceptions\NotConnectedException;

/**
 * An interactive SSH shell for sending and receiving text data
 */
class Shell
{

    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var mixed
     */
    protected $resource;

    /**
     * @var Terminal
     */
    protected $terminal;

    /**
     * This is a unique string that will include a timestamp, used to set the PS1 variable and aid the shell
     * in knowing when a command has completed execution
     *
     * @var string
     */
    protected $smart_marker = null;


    /**
     * Typically called from Connection::getShell()
     *
     * @param Connection $connection
     * @param Terminal   $terminal
     */
    function __construct(Connection $connection, Terminal $terminal)
    {
        if (!$connection->isConnected()) {
            throw new NotConnectedException();
        }

        if (!$connection->isAuthenticated()) {
            throw new NotAuthenticatedException();
        }

        $this->connection = $connection;
        $this->terminal   = $terminal;
        $this->resource   = ssh2_shell(
            $connection->getResource(),
            $terminal->getTerminalType(),
            $terminal->getEnv(),
            $terminal->getWidth(),
            $terminal->getHeight(),
            $terminal->getDimensionUnitType()
        );

    }

    /**
     * Read a given number of bytes - waiting until the count is matched
     *
     * @param $count
     * @return string
     */
    public function readBytes($count)
    {
        $data = '';
        while (strlen($data) < $count) {
            $data .= fread($this->resource, $count - strlen($data));

        }

        return $data;
    }

    /**
     * Read until a string marker is detected *anywhere in the response*
     *
     * @param string $marker
     * @return string
     */
    public function readUntilMarker($marker)
    {
        $data = '';

        do {
            $new = fread($this->resource, 8192);

            if ($new) {
                $data .= $new;

                if (strpos($data, $marker) !== false) {
                    break;
                }
            }
        } while (true);

        return $data;
    }

    /**
     * Read until a string marker is detected *at the end of the output only*
     *
     * NB: This will not be matched if a single packet sends the marker and additional content,
     *     The marker must be at the end of a packet, eg. the PS1 marker waiting for a new command
     *
     * @param string $marker A marker to stop reading when found
     * @param string $ignore A prefix to the marker that if found, will ignore the match
     * @return string
     */
    public function readUntilEndMarker($marker)
    {
        $data = '';

        do {
            $new = fread($this->resource, 8192);

            if ($new) {
                $data .= $new;

                if (substr($data, -strlen($marker)) == $marker) {
                    break;
                }
            }
        } while (true);

        return $data;
    }

    /**
     * Read until a regex is matched
     *
     * @param string $regex
     * @return string
     */
    public function readUntilExpression($regex)
    {
        $data = '';

        do {
            $new = fread($this->resource, 8192);

            if ($new) {
                $data .= $new;

                if (preg_match($regex, $data)) {
                    break;
                }
            }
        } while (true);

        return $data;
    }

    /**
     * Keep reading until there is no new data for a specified length of time
     *
     * @param float $delay
     * @return string
     */
    public function readUntilPause($delay = 1.0)
    {
        $data  = '';
        $start = microtime(true);

        while (microtime(true) - $start < $delay) {
            $new = fread($this->resource, 8192);

            // If we have new data, reset the timer and append
            if ($new) {
                $data .= $new;
                $start = microtime(true);
            }
        }

        return $data;
    }

    /**
     * Wait for content to be sent and then read until there is a pause
     *
     * @param float $delay
     * @return string
     */
    public function waitForContent($delay = 0.1)
    {
        return $this->readBytes(1).$this->readUntilPause($delay);
    }

    /**
     * Send text to the server
     *
     * @param string $txt
     * @param bool   $newLine
     */
    public function send($txt, $newLine = false)
    {
        fwrite($this->resource, $newLine ? ($txt."\n") : $txt);
    }

    /**
     * Send a line to the server
     *
     * @param $txt
     */
    public function sendln($txt)
    {
        $this->send($txt, true);
    }

    /**
     * Set the PS1 variable on the server so that smart commands will work
     */
    public function setSmartConsole()
    {
        // Set the smart marker with a timestamp to keep it unique from any references, etc
        $this->smart_marker = '#SMART:CONSOLE:MKR#'.time().'$';

        $this->sendln('PS1="'.$this->smart_marker.'"');
        $this->readUntilEndMarker($this->smart_marker);
    }

    /**
     * Send a command to the server and receive it's response
     *
     * @param string $command
     * @param bool   $trim Trim the command echo and PS1 marker from the response
     * @return string
     */
    public function sendSmartCommand($command, $trim = true)
    {
        if ($this->getSmartMarker() === null) {
            $this->setSmartConsole();
        }

        $this->sendln($command);
        $response = $this->readUntilEndMarker($this->getSmartMarker());

        return $trim ? trim(substr($response, strlen($command) + 1, -strlen($this->getSmartMarker()))) : $response;
    }

    /**
     * Get the currently active smart marker
     *
     * @return string
     */
    public function getSmartMarker()
    {
        return $this->smart_marker;
    }

    /**
     * Get the connection resource, allowing you to read/write to it
     *
     * @return mixed
     */
    public function getResource()
    {
        return $this->resource;
    }


}