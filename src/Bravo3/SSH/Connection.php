<?php
namespace Bravo3\SSH;

use Bravo3\SSH\Credentials\SSHCredential;
use Bravo3\SSH\Exceptions\FingerprintMismatchException;
use Bravo3\SSH\Exceptions\NotConnectedException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * A connection to an SSH server
 */
class Connection implements LoggerAwareInterface
{
    /**
     * @var string
     */
    protected $host = null;

    /**
     * @var int
     */
    protected $port = null;

    /**
     * @var SSHCredential
     */
    protected $credentials = null;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * The SSH session resource, if this is non-null it represents the connection is live
     *
     * @var mixed
     */
    protected $resource;

    /**
     * @var bool
     */
    protected $authenticated = false;

    function __construct($host, $port = 22, SSHCredential $credentials = null)
    {
        $this->host        = $host;
        $this->port        = $port;
        $this->credentials = $credentials;
    }


    /**
     * Connect to the SSH host
     *
     * @param string $fingerprint Optional - disconnect if there is a fingerprint mismatch
     * @return bool
     */
    public function connect($fingerprint = null)
    {
        if ($this->isConnected()) {
            $this->disconnect();
        }

        $this->resource = @ssh2_connect($this->getHost(), $this->getPort());

        // Check connection was a success
        if ($this->resource === false) {
            $this->log(LogLevel::ERROR, "Connection error");
            $this->disconnect();
            return false;
        }

        $this->log(LogLevel::INFO, "Connected to ".$this->getHost().":".$this->getPort());

        // Check fingerprint if provided
        if ($fingerprint && !$this->checkFingerprint($fingerprint)) {
            $this->log(LogLevel::WARNING, "Fingerprint mismatch");
            $this->disconnect();
            throw new FingerprintMismatchException();
        }

        // All good
        $this->authenticated = false;
        return true;
    }


    /**
     * Disconnect from a server and reset the connection state
     *
     * Will not throw an exception if there is no connection
     */
    public function disconnect()
    {
        $this->resource      = null;
        $this->authenticated = false;
        $this->log(LogLevel::INFO, "Disconnected");
    }

    /**
     * Get the server SSH fingerprint
     *
     * @see ssh2_fingerprint()
     * @param int $flags Equiv ssh2_fingerprint() flags
     * @throws Exceptions\NotConnectedException
     * @return string
     */
    public function getFingerprint($flags = null)
    {
        if ($flags === null) {
            // Default flags of the ssh2_fingerprint function, matches format of the known_hosts file
            $flags = SSH2_FINGERPRINT_MD5 | SSH2_FINGERPRINT_HEX;
        }

        if (!$this->isConnected()) {
            $this->log(LogLevel::ERROR, "Cannot get fingerprint - not connected");
            throw new NotConnectedException();
        }

        return ssh2_fingerprint($this->resource, $flags);
    }


    /**
     * Test the server fingerprint matches
     *
     * @param string $fingerprint
     * @return bool
     * @throws NotConnectedException
     */
    public function checkFingerprint($fingerprint)
    {
        return $this->getFingerprint() === $fingerprint;
    }

    /**
     * Authenticate to the server
     *
     * @return bool
     * @throws NotConnectedException
     */
    public function authenticate()
    {
        if (!$this->isConnected()) {
            $this->log(LogLevel::ERROR, "Cannot authenticate - not connected");
            throw new NotConnectedException();
        }

        return $this->authenticated = $this->getCredentials()->authenticate($this->resource);
    }


    /**
     * Execute a command on the SSH server
     *
     * @param string   $command
     * @param Terminal $terminal
     * @param string   $pty
     * @return ExecutionStream
     */
    public function execute($command, Terminal $terminal = null, $pty = null)
    {
        if ($terminal === null) {
            $terminal = new Terminal();
        }

        return new ExecutionStream($command, $this, $terminal, $pty);
    }

    /**
     * Get an interactive shell
     *
     * @param null $terminal
     * @return Shell
     */
    public function getShell($terminal = null)
    {
        if ($terminal === null) {
            $terminal = new Terminal();
        }

        return new Shell($this, $terminal);
    }


    // --

    /**
     * Set Credentials
     *
     * @param \Bravo3\SSH\Credentials\SSHCredential $credentials
     * @return Connection
     */
    public function setCredentials($credentials)
    {
        $this->credentials = $credentials;
        return $this;
    }

    /**
     * Get Credentials
     *
     * @return \Bravo3\SSH\Credentials\SSHCredential
     */
    public function getCredentials()
    {
        return $this->credentials;
    }

    /**
     * Set Host
     *
     * @param string $host
     * @return Connection
     */
    public function setHost($host)
    {
        $this->host = $host;
        return $this;
    }

    /**
     * Get Host
     *
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * Set Port
     *
     * @param int $port
     * @return Connection
     */
    public function setPort($port)
    {
        $this->port = (int)$port;
        return $this;
    }

    /**
     * Get Port
     *
     * @return int
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * Check if there is a live connection to an SSH server
     *
     * @return bool
     */
    public function isConnected()
    {
        return $this->resource !== null;
    }

    /**
     * Sets a logger instance on the object
     *
     * @param LoggerInterface $logger
     * @return null
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed  $level
     * @param string $message
     * @param array  $context
     */
    public function log($level, $message, array $context = array())
    {
        if (!$this->logger) {
            return;
        }
        $this->logger->log($level, $message, $context);
    }

    /**
     * Get the session resource
     *
     * @return mixed
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * Check if the current connection has passed authentication
     *
     * @return boolean
     */
    public function isAuthenticated()
    {
        return $this->authenticated;
    }


}
