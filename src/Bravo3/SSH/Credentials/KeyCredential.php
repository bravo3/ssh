<?php
namespace Bravo3\SSH\Credentials;

use Bravo3\SSH\Exceptions\FileNotExistsException;
use Bravo3\SSH\Exceptions\FileNotReadableException;
use Bravo3\SSH\Services\KeyUtility;

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

    /**
     * @var bool
     */
    protected $using_tmp_key = false;

    /**
     *
     *
     * @param string $username
     * @param string $public_key  Optional - will be generated automatically if null or omitted
     * @param string $private_key Path to private key file
     * @param string $password    Optional password to private key
     */
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

    function __destruct()
    {
        $this->destroyTmpKey();
    }

    /**
     * Set public key filename
     *
     * @param string $public_key
     * @return KeyCredential
     * @throws FileNotExistsException
     * @throws FileNotReadableException
     */
    public function setPublicKey($public_key)
    {
        $this->destroyTmpKey();

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
     * @throws FileNotExistsException
     * @throws FileNotReadableException
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
     * @throws FileNotExistsException
     * @throws FileNotReadableException
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
        if (!$this->getPrivateKey()) {
            throw new FileNotExistsException("Missing private key file");
        }

        if (!$this->getPublicKey()) {
            // We have a private key, but no public key - try to work out the public key from the private key
            $this->generatePublicKey();
        }

        return ssh2_auth_pubkey_file(
            $resource,
            $this->getUsername(),
            $this->getPublicKey(),
            $this->getPrivateKey(),
            $this->getPassword()
        );
    }

    /**
     * Automatically generate the public key from the private key
     *
     * The public key file will be removed when this class is destroyed. This function is automatically called when you
     * attempt to authenticate without a public key file.
     *
     * @param string $tmp_file Filename to save the public key, uses a temp file if omitted
     * @throws FileNotExistsException
     */
    public function generatePublicKey($tmp_file = null)
    {
        if (!$this->getPrivateKey()) {
            throw new FileNotExistsException("Missing private key file");
        }

        $util        = new KeyUtility();
        $pubkey      = $util->generateSshPublicKey('file://'.$this->getPrivateKey());
        $pubkey_file = $tmp_file ? : tempnam(sys_get_temp_dir(), 'ssh_pkey_');

        file_put_contents($pubkey_file, $pubkey);
        $this->public_key    = $pubkey_file;
        $this->using_tmp_key = true;
    }

    /**
     * Removes a temporary public key file
     */
    protected function destroyTmpKey()
    {
        if ($this->using_tmp_key && $this->public_key) {
            unlink($this->public_key);
            $this->using_tmp_key = false;
            $this->public_key    = null;
        }
    }

} 