<?php
namespace Bravo3\SSH;

use Bravo3\SSH\Enum\ShellType;
use Bravo3\SSH\Enum\StreamType;
use Bravo3\SSH\Exceptions\NotAuthenticatedException;
use Bravo3\SSH\Exceptions\NotConnectedException;
use Bravo3\SSH\Exceptions\UnsupportedException;
use Eloquent\Enumeration\Exception\UndefinedMemberException;

/**
 * An interactive SSH shell for sending and receiving text data
 */
class Shell
{
    const READ_SIZE = 8192;

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
     * @param StreamType $stream  Stream to read from, StreamType::COMBINED() if omitted
     * @return Output
     */
    public function readBytes($count, $timeout = 0, StreamType $stream = null)
    {
        $data      = new Output();
        $start     = microtime(true);
        $resources = $this->getResourcesForStream($stream);

        while ($timeout == 0 || (microtime(true) - $start < $timeout)) {
            foreach ($resources as $channel => $resource) {
                $new = fread($resource, $count - strlen($data));

                if ($new) {
                    $data->add($channel, $new);
                    $start = microtime(true);
                }

                if ($data->getCombinedSize() >= $count) {
                    break 2;
                }
            }
        }

        return $data;
    }

    /**
     * Read until a string marker is detected *anywhere in the response*
     *
     * Will return a string if a single stream is requested, else an Output object.
     *
     * @param string     $marker        A string to stop reading once found in the output
     * @param int        $timeout       Time in seconds to return if there is no new content
     * @param bool       $normalise_eol Convert CRLF to LF
     * @param StreamType $stream        Stream to read from, StreamType::COMBINED() if omitted
     * @return Output
     */
    public function readUntilMarker($marker, $timeout = 0, $normalise_eol = false, StreamType $stream = null)
    {
        $data      = new Output();
        $start     = microtime(true);
        $resources = $this->getResourcesForStream($stream);

        while ($timeout == 0 || (microtime(true) - $start < $timeout)) {
            foreach ($resources as $channel => $resource) {
                $new = fread($resource, self::READ_SIZE);

                if ($new) {
                    if ($normalise_eol) {
                        $new = str_replace("\r\n", "\n", $new);
                    }

                    $data->add($channel, $new);
                    $start = microtime(true);

                    if (strpos($data->getAll(), $marker) !== false) {
                        break 2;
                    }
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
     * @param StreamType $stream        Stream to read from, StreamType::COMBINED() if omitted
     * @return Output
     */
    public function readUntilEndMarker($marker, $timeout = 0, $normalise_eol = false, StreamType $stream = null)
    {
        $data       = new Output();
        $start      = microtime(true);
        $marker_len = strlen($marker);
        $resources  = $this->getResourcesForStream($stream);

        while (($timeout == 0) || (microtime(true) - $start < $timeout)) {
            foreach ($resources as $channel => $resource) {
                $new = fread($resource, self::READ_SIZE);

                if ($new) {
                    if ($normalise_eol) {
                        $new = str_replace("\r\n", "\n", $new);
                    }

                    $data->add($channel, $new);
                    $start = microtime(true);

                    if (substr($data->getAll(), -$marker_len) == $marker) {
                        break 2;
                    }
                }
            }
        }

        return $data;
    }

    /**
     * Read until a regex is matched
     *
     * @param string     $regex
     * @param int        $timeout       Time in seconds to return if there is no new content
     * @param bool       $normalise_eol Convert CRLF to LF
     * @param StreamType $stream        Stream to read from, StreamType::COMBINED() if omitted
     * @return Output
     */
    public function readUntilExpression($regex, $timeout = 0, $normalise_eol = false, StreamType $stream = null)
    {
        $data      = new Output();
        $start     = microtime(true);
        $resources = $this->getResourcesForStream($stream);

        while ($timeout == 0 || (microtime(true) - $start < $timeout)) {
            foreach ($resources as $channel => $resource) {
                $new = fread($resource, self::READ_SIZE);

                if ($new) {
                    if ($normalise_eol) {
                        $new = str_replace("\r\n", "\n", $new);
                    }

                    $data->add($channel, $new);
                    $start = microtime(true);

                    if (preg_match_all($regex, $data->getAll()) > 0) {
                        break 2;
                    }
                }
            }
        }

        return $data;
    }

    /**
     * Keep reading until there is no new data for a specified length of time
     *
     * @param float      $delay         Time in seconds to return if there is no new content
     * @param bool       $normalise_oel Convert CRLF to LF
     * @param StreamType $stream        Stream to read from, StreamType::COMBINED() if omitted
     * @return Output
     */
    public function readUntilPause($delay = 1.0, $normalise_oel = false, StreamType $stream = null)
    {
        $data      = new Output();
        $start     = microtime(true);
        $resources = $this->getResourcesForStream($stream);

        while (microtime(true) - $start < $delay) {
            foreach ($resources as $channel => $resource) {
                $new = fread($resource, self::READ_SIZE);

                // If we have new data, reset the timer and append
                if ($new) {
                    if ($normalise_oel) {
                        $new = str_replace("\r\n", "\n", $new);
                    }

                    $data->add($channel, $new);
                    $start = microtime(true);
                }
            }
        }

        return $data;
    }

    /**
     * Wait for content to be sent and then read until there is a pause
     *
     * @param float      $delay         Time in seconds to return if there is no new content
     * @param bool       $normalise_oel Convert CRLF to LF
     * @param StreamType $stream        Stream to read from, StreamType::COMBINED() if omitted
     * @param int        $timeout       Timeout in seconds before giving up waiting for content
     * @return Output
     */
    public function waitForContent($delay = 0.1, $normalise_oel = false, StreamType $stream = null, $timeout = 0.0)
    {
        $pre  = $this->readBytes(1, $timeout, $stream);
        $post = $this->readUntilPause($delay, $normalise_oel);
        return new Output(array_merge($pre->getBuffer(), $post->getBuffer()));
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

        $this->readUntilEndMarker($this->smart_marker, 15.0);
    }

    /**
     * Send a command to the server and receive it's response
     *
     * @param string     $command
     * @param bool       $trim          Trim the command echo and PS1 marker from the response
     * @param int        $timeout       Time in seconds to return if there is no new content
     * @param bool       $normalise_eol Convert CRLF to LF
     * @param StreamType $stream        Stream to read from, StreamType::COMBINED() if omitted
     * @return Output
     */
    public function sendSmartCommand(
        $command,
        $trim = true,
        $timeout = 0,
        $normalise_eol = false,
        StreamType $stream = null
    ) {
        if ($this->getSmartMarker() === null) {
            $this->setSmartConsole();
        }

        $this->sendln($command);
        $response = $this->readUntilEndMarker($this->getSmartMarker(), $timeout, $normalise_eol, $stream);

        if ($trim) {
            $buffer      = [];
            $buffer_size = count($response->getBuffer());
            foreach ($response as $index => $line) {
                $channel = $line[0];
                $data    = $line[1];

                // Trim command echo
                if ($channel == StreamType::STDIO && $index == 0) {
                    if (substr($data, 0, strlen($command)) == $command) {
                        $data = substr($data, strlen($command) + 1);
                    }

                    $data = ltrim($data);
                }

                // Trim PS1/prompt
                if ($channel == StreamType::STDIO && $index == ($buffer_size - 1)) {
                    if (substr($data, -strlen($this->getSmartMarker())) == $this->getSmartMarker()) {
                        $data = substr($data, 0, -strlen($this->getSmartMarker()));
                    }

                    $data = rtrim($data);
                }

                $buffer[] = [$channel, $data];
            }

            $response = new Output($buffer);
        }

        return $response;
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
     * This function will send a command to the remote host, if the shell isn't ready for input then this might not
     * be a quick function call. A default timeout safeguards against hangs - to improve this, ensure the shell is
     * ready for commands.
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
     * Return an array of required resources against their channel index
     *
     * @param StreamType $stream
     * @return array<int, resource>
     */
    protected function getResourcesForStream(StreamType $stream = null)
    {
        switch ($stream) {
            case StreamType::STDIO():
                return [StreamType::STDIO => $this->resource];
            case StreamType::STDERR():
                return [StreamType::STDERR => $this->std_err];
            default:
            case StreamType::COMBINED():
                return [StreamType::STDERR => $this->std_err, StreamType::STDIO => $this->resource];
        }
    }

}