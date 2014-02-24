<?php
namespace NovaTek\SSH\Credentials;

class PasswordCredential extends SSHCredential
{

    /**
     * @var string
     */
    protected $password;

    function __construct($username = 'root', $password = null)
    {
        $this->setUsername($username);
        $this->setPassword($password);
    }

    /**
     * Set Password
     *
     * @param string $password
     * @return PasswordCredential
     */
    public function setPassword($password)
    {
        $this->password = $password;
        return $this;
    }

    /**
     * Get Password
     *
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Authenticate against a given resource
     *
     * @param mixed $resource
     * @return bool
     */
    public function authenticate($resource)
    {
        return ssh2_auth_password($resource, $this->getUsername(), $this->getPassword());
    }


}