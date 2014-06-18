<?php
namespace Bravo3\SSH\Services;

use Bravo3\SSH\Exceptions\FileNotReadableException;

class OpenSSL
{
    function __construct()
    {
        if (!function_exists('openssl_pkey_new')) {
            throw new \RuntimeException("OpenSSL extension not loaded");
        }
    }


    /**
     * PEM private -> public key generation
     *
     * $private_key can be one of the following:
     *  * A string having the format file:///path/to/file.pem
     *    The named file must contain a PEM encoded certificate/private key (it may contain both)
     *  * A PEM formatted private key.
     *
     * @param string $private_key
     * @param string $passphrase
     * @return string
     */
    public function generatePublicKey($private_key, $passphrase = '')
    {
        $res = @openssl_pkey_get_private($private_key, $passphrase);

        if (!$res) {
            throw new FileNotReadableException("Unable to read private key");
        }

        // Get public key data
        $openssl_data = openssl_pkey_get_details($res);

        // The OpenSSL certificate
        $openssl_key = $openssl_data['key'];

        // Certificate format (RSA and DSA supported)
        if (isset($openssl_data['rsa'])) {
            $openssl_format = 'rsa';
        } elseif (isset($openssl_data['dsa'])) {
            $openssl_format = 'dss';
        } else {
            throw new \RuntimeException("Unknown key format");
        }

        // Convert the key to an OpenSSH key
        $openssh_key = null;
        // http://stackoverflow.com/questions/5524121/converting-an-openssl-generated-rsa-public-key-to-openssh-format-php

        return $openssh_key;
    }

}
