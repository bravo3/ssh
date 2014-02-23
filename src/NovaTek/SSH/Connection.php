<?php
namespace NovaTek\SSH;

use NovaTek\SSH\Credentials\SSHCredential;
use NovaTek\SSH\Exceptions\NotConnectedException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

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
     * @var string
     */
    protected $resource;

    function __construct($host, $port = 22, SSHCredential $credentials = null)
    {
        $this->host        = $host;
        $this->port        = $port;
        $this->credentials = $credentials;
    }


    /**
     * Connect to the SSH host
     *
     * @return bool
     */
    public function connect()
    {
        if ($this->isConnected()) $this->disconnect();

        return false;
    }


    /**
     * Disconnect from a server
     *
     * Will not throw an exception if there is no connection
     */
    public function disconnect()
    {

        $this->resource = null;
    }

    /**
     * Get the server SSH fingerprint
     *
     * @return string
     * @throws NotConnectedException
     */
    public function getFingerprint()
    {
        if (!$this->isConnected()) {
            $this->log(LogLevel::ERROR, "Cannot get fingerprint - not connected");
            throw new NotConnectedException();
        }
        return '';
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
        return false;
    }





    // --

    /**
     * Set Credentials
     *
     * @param \NovaTek\SSH\Credentials\SSHCredential $credentials
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
     * @return \NovaTek\SSH\Credentials\SSHCredential
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
    public function log($level = LogLevel::INFO, $message, array $context = array())
    {
        if (!$this->logger) return;
        $this->logger->log($level, $message, $context);
    }

}
