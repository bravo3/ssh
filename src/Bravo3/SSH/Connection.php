<?php
namespace Bravo3\SSH;

use Bravo3\SSH\Credentials\SSHCredential;
use Bravo3\SSH\Exceptions\FingerprintMismatchException;
use Bravo3\SSH\Exceptions\NotAuthenticatedException;
use Bravo3\SSH\Exceptions\NotConnectedException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;

/**
 * A connection to an SSH server
 */
class Connection implements LoggerAwareInterface
{
    const ERR_CONNECTION           = "Connection error";
    const ERR_FINGERPRINT_MISMATCH = "Fingerprint mismatch";
    const ERR_NOT_CONNECTED        = "Not connected";
    const ERR_NOT_AUTHENTICATED    = "Not authenticated";
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
    protected $resource = null;

    /**
     * The SSH session resource, if this is non-null it represents the connection is live
     *
     * @var Connection
     */
    protected $parent = null;

    /**
     * @var bool
     */
    protected $authenticated = false;

    function __construct($host, $port = 22, SSHCredential $credentials = null)
    {
        $this->host        = $host;
        $this->port        = $port;
        $this->credentials = $credentials;
        $this->setLogger(new NullLogger());
    }

    /**
     * Tunnel to another SSH server
     *
     * @param string        $host
     * @param int           $port
     * @param SSHCredential $credentials
     * @return Connection|null Returns null if the connection failed
     */
    public function tunnel($host, $port = 22, SSHCredential $credentials = null)
    {
        $this->requireConnection();
        $this->log(LogLevel::INFO, "Creating tunnel to ".$host.":".$port);

        $tunnel_resource = @ssh2_tunnel($this->resource, $host, $port);
        if (!$tunnel_resource) {
            $this->log(LogLevel::ERROR, self::ERR_CONNECTION);
            return null;
        }

        $tunnel = new Connection($host, $port, $credentials);
        $r      = new \ReflectionClass($tunnel);

        $r_resource = $r->getProperty('resource');
        $r_resource->setAccessible(true);
        $r_resource->setValue($tunnel, $tunnel_resource);

        $r_parent = $r->getProperty('parent');
        $r_parent->setAccessible(true);
        $r_parent->setValue($tunnel, $this);

        return $tunnel;
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
            $this->log(LogLevel::ERROR, self::ERR_CONNECTION);
            $this->disconnect();
            return false;
        }

        $this->log(LogLevel::INFO, "Connected to ".$this->getHost().":".$this->getPort());

        // Check fingerprint if provided
        if ($fingerprint && !$this->checkFingerprint($fingerprint)) {
            $this->log(LogLevel::WARNING, self::ERR_FINGERPRINT_MISMATCH);
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
     * Disconnect this connection and all parent connections in the tunnel chain
     */
    public function disconnectChain()
    {
        $connection = $this;

        do {
            $connection->disconnect();
        } while ($connection = $connection->getParent());
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

        $this->requireConnection(false);
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
        $this->requireConnection(false);
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
        $this->requireConnection();
        return new ExecutionStream($command, $this, $terminal ? : new Terminal(), $pty);
    }

    /**
     * Get an interactive shell
     *
     * @param Terminal $terminal
     * @return Shell
     */
    public function getShell(Terminal $terminal = null)
    {
        $this->requireConnection();
        return new Shell($this, $terminal ? : new Terminal());
    }


    /**
     * Send a file to the server via SCP
     *
     * @param string $local  Filename of local file
     * @param string $remote Filename for the destination file
     * @param int    $create_mode
     * @return boolean
     */
    public function scpSend($local, $remote, $create_mode = 0644)
    {
        $this->requireConnection();
        return @ssh2_scp_send($this->resource, $local, $remote, $create_mode);
    }

    /**
     * Send a file to the server via SCP
     *
     * @param string $remote Filename of the source file on the remote
     * @param string $local  Filename for the destination on the local machine
     * @param int    $create_mode
     * @return boolean
     */
    public function scpReceive($remote, $local)
    {
        $this->requireConnection();
        return @ssh2_scp_recv($this->resource, $remote, $local);
    }

    /**
     * Throw an exception if the state is not connected and authenticated
     *
     * @param bool $authenticationRequired
     * @throws Exceptions\NotAuthenticatedException
     * @throws Exceptions\NotConnectedException
     */
    protected function requireConnection($authenticationRequired = true)
    {
        if (!$this->isConnected()) {
            $this->log(LogLevel::ERROR, self::ERR_NOT_CONNECTED);
            throw new NotConnectedException();
        }

        if ($authenticationRequired && !$this->isAuthenticated()) {
            $this->log(LogLevel::ERROR, self::ERR_NOT_AUTHENTICATED);
            throw new NotAuthenticatedException();
        }
    }


    // --

    /**
     * Set Credentials
     *
     * @param SSHCredential $credentials
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
     * @return SSHCredential
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

    /**
     * Get parent connection
     *
     * If this connection is via a tunnel, this will contain the Connection from which it was established.
     *
     * @return Connection|null
     */
    public function getParent()
    {
        return $this->parent;
    }


}
