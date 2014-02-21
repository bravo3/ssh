<?php
namespace NovaTek\Component\SSH\Credentials;

use NovaTek\Component\SSH\Exceptions\FileNotExistsException;
use NovaTek\Component\SSH\Exceptions\FileNotReadableException;

class KeyCredential extends SSHCredential
{

    /**
     * @var string
     */
    protected $key;

    function __construct($username = 'root', $key = null)
    {
        $this->setUsername($username);
        $this->key = $key;
    }

    /**
     * Set Key
     *
     * @param string $key
     * @return KeyCredential
     */
    public function setKey($key)
    {
        $this->key = $key;
        return $this;
    }

    /**
     * Get Key
     *
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }


    /**
     * Load the SSH key from a keyfile
     *
     * @param $fn
     */
    public function loadFromKeyfile($fn)
    {
        if (!file_exists($fn)) throw new FileNotExistsException($fn);
        if (!is_readable($fn)) throw new FileNotReadableException($fn);
        $this->setKey(file_get_contents($fn));
    }

} 