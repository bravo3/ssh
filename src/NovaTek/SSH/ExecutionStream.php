<?php
namespace NovaTek\SSH;

use NovaTek\SSH\Exceptions\NotAuthenticatedException;
use NovaTek\SSH\Exceptions\NotConnectedException;
use NovaTek\SSH\Exceptions\StreamNotOpenException;

/**
 * Created by an SSH exec call, this class allows you to read the response
 */
class ExecutionStream
{

    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var Terminal
     */
    protected $terminal;

    /**
     * @var string
     */
    protected $command;

    /**
     * @var mixed
     */
    protected $resource;

    /**
     * @var bool
     */
    protected $open = true;

    /**
     * Typically called from Connection::execute()
     *
     * @param string     $command
     * @param Connection $connection
     * @param Terminal   $terminal
     * @param string     $pty
     */
    function __construct($command, Connection $connection, Terminal $terminal, $pty = null)
    {
        $this->command    = $command;
        $this->connection = $connection;
        $this->terminal   = $terminal;

        if (!$connection->isConnected()) {
            throw new NotConnectedException();
        }

        if (!$connection->isAuthenticated()) {
            throw new NotAuthenticatedException();
        }

        $this->resource = ssh2_exec(
            $connection->getResource(),
            $command,
            $pty,
            $terminal->getEnv(),
            $terminal->getWidth(),
            $terminal->getHeight(),
            $terminal->getDimensionUnitType()
        );
    }

    /**
     * Get the 'stdout' output from the command execution
     *
     * @return string
     */
    public function getOutput()
    {
        $this->close();
        stream_set_blocking($this->resource, true);
        return stream_get_contents($this->resource);
    }

    /**
     * Get the complete output from the command, separating stdout and stderr
     *
     * Returns an associative array:
     * ['stdout' => string, 'stderr' => string]
     *
     * @return string[]
     */
    public function getSegmentedOutput()
    {
        $this->close();

        $stderr = ssh2_fetch_stream($this->resource, SSH2_STREAM_STDERR);

        stream_set_blocking($this->resource, true);
        stream_set_blocking($stderr, true);

        $out = stream_get_contents($this->resource);
        $err = stream_get_contents($stderr);

        return ['stdout' => $out, 'stderr' => $err];
    }

    /**
     * Checks if we are still able to receive output - this closes once the command output has been received
     *
     * @return boolean
     */
    public function isOpen()
    {
        return $this->open;
    }

    /**
     * We've completed the transaction, forbid additional requests for output
     *
     * @throws StreamNotOpenException
     */
    protected function close()
    {
        if (!$this->open) {
            throw new StreamNotOpenException('The execution process has already completed');
        }

        $this->open = false;
    }

} 