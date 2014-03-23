<?php
namespace Bravo3\SSH\Credentials;

use Bravo3\SSH\Exceptions\FileNotExistsException;
use Bravo3\SSH\Exceptions\FileNotReadableException;

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

    function __construct($username = 'root', $public_key = null, $private_key = null, $password = null)
    {
        $this->setUsername($username);

        if ($public_key) {
            $this->setPublicKey($public_key);
        }

        if ($private_key) {
            $this->setPrivateKey($private_key);
        }

        $this->setPassword($password);
    }


    /**
     * Set public key filename
     *
     * @param string $public_key
     * @return KeyCredential
     * @throws \Bravo3\SSH\Exceptions\FileNotExistsException
     * @throws \Bravo3\SSH\Exceptions\FileNotReadableException
     */
    public function setPublicKey($public_key)
    {
        // Check file is readable
        if (!file_exists($public_key)) {
            throw new FileNotExistsException($public_key);
        }
        if (!is_readable($public_key) || is_dir($public_key)) {
            throw new FileNotReadableException($public_key);
        }

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
     * @throws \Bravo3\SSH\Exceptions\FileNotExistsException
     * @throws \Bravo3\SSH\Exceptions\FileNotReadableException
     */
    public function setPrivateKey($private_key)
    {
        // Check file is readable
        if (!file_exists($private_key)) {
            throw new FileNotExistsException($private_key);
        }
        if (!is_readable($private_key) || is_dir($private_key)) {
            throw new FileNotReadableException($private_key);
        }

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
     * @param string $public
     * @param string $private
     * @param string $password
     * @throws \Bravo3\SSH\Exceptions\FileNotExistsException
     * @throws \Bravo3\SSH\Exceptions\FileNotReadableException
     */
    public function setKeyPair($public, $private, $password = null)
    {
        $this->setPublicKey($public);
        $this->setPrivateKey($private);
        $this->setPassword($password);
    }

    /**
     * Authenticate against a given resource
     *
     * @param mixed $resource
     * @return bool
     */
    public function authenticate($resource)
    {
        return ssh2_auth_pubkey_file(
            $resource,
            $this->getUsername(),
            $this->getPublicKey(),
            $this->getPrivateKey(),
            $this->getPassword()
        );
    }


} 