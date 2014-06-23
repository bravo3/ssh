<?php
namespace Bravo3\SSH;

use Bravo3\SSH\Enum\ShellType;
use Bravo3\SSH\Enum\StreamType;
use Bravo3\SSH\Exceptions\NotAuthenticatedException;
use Bravo3\SSH\Exceptions\NotConnectedException;
use Eloquent\Enumeration\Exception\UndefinedMemberException;

/**
 * An interactive SSH shell for sending and receiving text data
 */
class Shell
{
    const STREAM_STDIO  = 0;
    const STREAM_STDERR = 1;

    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var resource
     */
    protected $resource;

    /**
     * @var resource
     */
    protected $std_err;

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
     * @var ShellType
     */
    protected $shell_type;

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

        $this->shell_type = null;
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

        // TODO: Does this force stdio to be limited to stdout?
        $this->std_err = ssh2_fetch_stream($this->resource, SSH2_STREAM_STDERR);
    }

    /**
     * Read a given number of bytes - waiting until the count is matched
     *
     * @param int        $count   Number of bytes to read
     * @param int        $timeout Time in seconds to return if there is no new content
     * @param StreamType $stream  Stream to read from, STDIO if omitted
     * @return string
     */
    public function readBytes($count, $timeout = 0, StreamType $stream = null)
    {
        $data     = '';
        $start    = microtime(true);
        $resource = $this->getResourceForStream($stream);

        while ((strlen($data) < $count) && ($timeout == 0 || (microtime(true) - $start < $timeout))) {
            $new = fread($resource, $count - strlen($data));

            if ($new) {
                $data .= $new;
                $start = microtime(true);
            }
        }

        return $data;
    }

    /**
     * Read until a string marker is detected *anywhere in the response*
     *
     * @param string $marker        A string to stop reading once found in the output
     * @param int    $timeout       Time in seconds to return if there is no new content
     * @param bool   $normalise_eol Convert CRLF to LF
     * @return string
     */
    public function readUntilMarker($marker, $timeout = 0, $normalise_eol = false, StreamType $stream = null)
    {
        $data     = '';
        $start    = microtime(true);
        $resource = $this->getResourceForStream($stream);

        while ($timeout == 0 || (microtime(true) - $start < $timeout)) {
            $new = fread($resource, 8192);

            if ($new) {
                $data .= $new;
                $start = microtime(true);

                if ($normalise_eol) {
                    $data = str_replace("\r\n", "\n", $data);
                }

                if (strpos($data, $marker) !== false) {
                    break;
                }
            }
        }

        return $data;
    }

    /**
     * Read until a string marker is detected *at the end of the output only*
     *
     * NB: This will not be matched if a single packet sends the marker and additional content,
     *     The marker must be at the end of a packet, eg. the PS1 marker waiting for a new command
     *
     * @param string     $marker        A string to stop reading once found at the end of the output
     * @param int        $timeout       Time in seconds to return if there is no new content
     * @param bool       $normalise_eol Convert CRLF to LF
     * @param StreamType $stream
     * @return string
     */
    public function readUntilEndMarker($marker, $timeout = 0, $normalise_eol = false, StreamType $stream = null)
    {
        $data       = '';
        $start      = microtime(true);
        $marker_len = strlen($marker);
        $resource   = $this->getResourceForStream($stream);

        while ($timeout == 0 || (microtime(true) - $start < $timeout)) {
            $new = fread($resource, 8192);

            if ($new) {
                $data .= $new;
                $start = microtime(true);

                if ($normalise_eol) {
                    $data = str_replace("\r\n", "\n", $data);
                }


                if (substr($data, -$marker_len) == $marker) {
                    break;
                }
            }
        }

        return $data;
    }

    /**
     * Read until a regex is matched
     *
     * @param string $regex
     * @param int    $timeout       Time in seconds to return if there is no new content
     * @param bool   $normalise_eol Convert CRLF to LF
     * @return string
     */
    public function readUntilExpression($regex, $timeout = 0, $normalise_eol = false, StreamType $stream = null)
    {
        $data     = '';
        $start    = microtime(true);
        $resource = $this->getResourceForStream($stream);

        while ($timeout == 0 || (microtime(true) - $start < $timeout)) {
            $new = fread($resource, 8192);

            if ($new) {
                $data .= $new;
                $start = microtime(true);

                if ($normalise_eol) {
                    $data = str_replace("\r\n", "\n", $data);
                }

                if (preg_match_all($regex, $data) > 0) {
                    break;
                }
            }
        }

        return $data;
    }

    /**
     * Keep reading until there is no new data for a specified length of time
     *
     * @param float $delay         Time in seconds to return if there is no new content
     * @param bool  $normalise_oel Convert CRLF to LF
     * @return string
     */
    public function readUntilPause($delay = 1.0, $normalise_oel = false, StreamType $stream = null)
    {
        $data     = '';
        $start    = microtime(true);
        $resource = $this->getResourceForStream($stream);

        while (microtime(true) - $start < $delay) {
            $new = fread($resource, 8192);

            // If we have new data, reset the timer and append
            if ($new) {
                $data .= $new;
                $start = microtime(true);

                if ($normalise_oel) {
                    $data = str_replace("\r\n", "\n", $data);
                }
            }
        }

        return $data;
    }

    /**
     * Wait for content to be sent and then read until there is a pause
     *
     * @param float      $delay
     * @param bool       $normalise_oel
     * @param StreamType $stream
     * @return string
     */
    public function waitForContent($delay = 0.1, $normalise_oel = false, StreamType $stream = null)
    {
        return $this->readBytes(1).$this->readUntilPause($delay, $normalise_oel);
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
     * Set the PS1/prompt variable on the server so that smart commands will work
     *
     * @param string    $marker     Leave blank to use a random marker
     * @param ShellType $shell_type Will auto-detect is omitted
     */
    public function setSmartConsole($marker = null, ShellType $shell_type = null)
    {
        // Set the smart marker with a timestamp to keep it unique from any references, etc
        $this->smart_marker = $marker ? : '#:MKR#'.time().'$';

        if ($shell_type === null) {
            $shell_type = $this->getShellType();
        }

        switch ($shell_type) {
            // Bourne-shell compatibles
            default:
                $this->sendln('export PS1="'.$this->smart_marker.'"');
                break;
            // C-shell compatibles
            case ShellType::CSH():
            case ShellType::TCSH():
                $this->sendln('set prompt="'.$this->smart_marker.'"');
                break;
        }

        $this->readUntilEndMarker($this->smart_marker);
    }

    /**
     * Send a command to the server and receive it's response
     *
     * @param string $command
     * @param bool   $trim          Trim the command echo and PS1 marker from the response
     * @param int    $timeout       Time in seconds to return if there is no new content
     * @param bool   $normalise_eol Convert CRLF to LF
     * @return string
     */
    public function sendSmartCommand($command, $trim = true, $timeout = 0, $normalise_eol = false)
    {
        if ($this->getSmartMarker() === null) {
            $this->setSmartConsole();
        }

        $this->sendln($command);
        $response = $this->readUntilEndMarker($this->getSmartMarker(), $timeout, $normalise_eol);

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
     * @return resource
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * Get the stderr resource
     *
     * @return resource
     */
    public function getStdErr()
    {
        return $this->std_err;
    }


    /**
     * Resolve the shell type
     *
     * @param float $timeout
     * @return ShellType
     */
    public function getShellType($timeout = 15.0)
    {
        if ($this->shell_type !== null) {
            return $this->shell_type;
        }

        $this->waitForContent(1);
        $this->sendln("echo $0");

        $regex = '/echo \$0$\n^(\-[a-z]+)/sm';
        $out   = $this->readUntilExpression($regex, $timeout, true);

        $matches = null;
        preg_match_all($regex, $out, $matches);
        $shell_name = @$matches[1][0];

        // $0 over SSH may prefix with a hyphen
        if ($shell_name{0} == '-') {
            $shell_name = substr($shell_name, 1);
        }

        try {
            $this->shell_type = ShellType::memberByValue($shell_name);
        } catch (UndefinedMemberException $e) {
            $this->shell_type = ShellType::UNKNOWN();
        }

        return $this->shell_type;
    }

    /**
     * Return the correct resource for a StreamType
     *
     * @param StreamType $stream
     * @return resource
     */
    protected function getResourceForStream(StreamType $stream = null)
    {
        switch ($stream) {
            default:
            case StreamType::STDIO():
                return $this->resource;
            case StreamType::STDERR():
                return $this->std_err;
        }
    }

}