<?php
namespace NovaTek\Component\SSH;

use NovaTek\Component\SSH\Credentials\SSHCredential;

class Connection
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


    function __construct($host, $port = 22, SSHCredential $credentials = null)
    {
        $this->host = $host;
        $this->port = $port;
        $this->credentials = $credentials;
    }


    // --

    /**
     * Set Credentials
     *
     * @param \NovaTek\Component\SSH\Credentials\SSHCredential $credentials
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
     * @return \NovaTek\Component\SSH\Credentials\SSHCredential
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


}
