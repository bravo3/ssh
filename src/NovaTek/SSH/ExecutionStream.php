<?php
namespace NovaTek\SSH;

use NovaTek\SSH\Exceptions\NotAuthenticatedException;
use NovaTek\SSH\Exceptions\NotConnectedException;

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
     * Execute a command create a new execution stream
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
     * Get the complete output from the command
     *
     * @return string
     */
    public function getOutput()
    {
        $this->setBlocking(true);   // important
        return stream_get_contents($this->resource);
    }


    /**
     * Set the stream blocking
     *
     * @param bool $block
     */
    public function setBlocking($block)
    {
        stream_set_blocking($this->resource, (bool)$block);
    }

} 