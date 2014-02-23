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
        $this->password = $password;
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



} 