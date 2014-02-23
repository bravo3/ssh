<?php
namespace NovaTek\SSH\Credentials;

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



} 