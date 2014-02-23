<?php
namespace NovaTek\SSH\Credentials;

use NovaTek\SSH\Exceptions\FileNotExistsException;
use NovaTek\SSH\Exceptions\FileNotReadableException;

class KeyCredential extends SSHCredential
{

    /**
     * @var string
     */
    protected $private_key;

    /**
     * @var string
     */
    protected $public_key;

    /**
     * @var string
     */
    protected $password;

    function __construct($username = 'root', $key = null)
    {
        $this->setUsername($username);
        $this->private_key = $key;
    }


    /**
     * Set public key filename
     *
     * @param string $public_key
     * @return KeyCredential
     */
    public function setPublicKey($public_key)
    {
        $this->public_key = $public_key;
        return $this;
    }

    /**
     * Get public key filename
     *
     * @return string
     */
    public function getPublicKey()
    {
        return $this->public_key;
    }

    /**
     * Set private key filename
     *
     * @param string $private_key
     * @return KeyCredential
     */
    public function setPrivateKey($private_key)
    {
        $this->private_key = $private_key;
        return $this;
    }

    /**
     * Get private key filename
     *
     * @return string
     */
    public function getPrivateKey()
    {
        return $this->private_key;
    }

    /**
     * Set private key file password
     *
     * @param string $password
     * @return KeyCredential
     */
    public function setPassword($password)
    {
        $this->password = $password;
        return $this;
    }

    /**
     * Get private key file password
     *
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Load the SSH key from a keyfile
     *
     * @param $fn
     */
    public function setKeyPair($public, $private, $password = null)
    {
        $this->password = $password;

        if (!file_exists($public)) throw new FileNotExistsException($public);
        if (!is_readable($public)) throw new FileNotReadableException($public);

        if (!file_exists($private)) throw new FileNotExistsException($private);
        if (!is_readable($private)) throw new FileNotReadableException($private);

        $this->setPublicKey($public);
        $this->setPrivateKey($private);
        $this->setPassword($password);
    }


} 