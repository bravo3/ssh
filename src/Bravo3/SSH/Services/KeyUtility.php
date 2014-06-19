<?php
namespace Bravo3\SSH\Services;

use Bravo3\SSH\Exceptions\FileNotReadableException;

class KeyUtility
{
    function __construct()
    {
        if (!function_exists('openssl_pkey_new')) {
            throw new \RuntimeException("OpenSSL extension not loaded");
        }
    }


    /**
     * PEM private -> SSH public key generation
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
    public function generateSshPublicKey($private_key, $passphrase = '')
    {
        // // http://stackoverflow.com/questions/5524121/converting-an-openssl-generated-rsa-public-key-to-openssh-format-php
        $res = @openssl_pkey_get_private($private_key, $passphrase);

        if (!$res) {
            throw new FileNotReadableException("Unable to read private key");
        }

        // Get public key data
        $key = openssl_pkey_get_details($res);

        // Certificate format (RSA and DSA supported)
        if (isset($key['rsa'])) {
            return $this->sshEncodeRsaPublicKey($key);
        } elseif (isset($key['dsa'])) {
            return $this->sshEncodeDsaPublicKey($key);
        } else {
            throw new \RuntimeException("Unknown key format");
        }
    }

    /**
     * Encode an RSA public key
     *
     * @param string $key Data returned by openssl_pkey_get_details
     * @return string
     */
    protected function sshEncodeRsaPublicKey($key, $hint = '')
    {
        $buffer = pack("N", 7)."ssh-rsa".
                  $this->sshEncodeBuffer($key['rsa']['e']).
                  $this->sshEncodeBuffer($key['rsa']['n']);

        return "ssh-rsa ".base64_encode($buffer).' '.$hint;
    }

    /**
     * Encode an DSA public key
     *
     * @param string $key Data returned by openssl_pkey_get_details
     * @return string
     */
    protected function sshEncodeDsaPublicKey($key, $hint = '')
    {
        $buffer = pack("N", 7)."ssh-dss".
                  $this->sshEncodeBuffer($key['dsa']['p']).
                  $this->sshEncodeBuffer($key['dsa']['q']).
                  $this->sshEncodeBuffer($key['dsa']['g']).
                  $this->sshEncodeBuffer($key['dsa']['pub_key']);

        return "ssh-dss ".base64_encode($buffer).' '.$hint;
    }

    /**
     * Pack a key buffer
     *
     * @param $buffer
     * @return string
     */
    protected function sshEncodeBuffer($buffer)
    {
        $len = strlen($buffer);
        if (ord($buffer[0]) & 0x80) {
            $len++;
            $buffer = "\x00".$buffer;
        }

        return pack("Na*", $len, $buffer);
    }

}
