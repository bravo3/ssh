<?php
namespace Bravo3\SSH\Credentials;

abstract class SSHCredential
{

    /**
     * @var string
     */
    protected $username;

    /**
     * Set Username
     *
     * @param string $username
     * @return SSHCredential
     */
    public function setUsername($username)
    {
        $this->username = $username;
        return $this;
    }

    /**
     * Get Username
     *
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Authenticate against a given resource
     *
     * @param mixed $resource
     * @return bool
     */
    abstract public function authenticate($resource);

} 