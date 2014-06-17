<?php
namespace SSH\Credentials;

use Bravo3\SSH\Connection;
use Bravo3\SSH\Credentials\KeyCredential;

class PublicKeyTest extends \PHPUnit_Framework_TestCase
{
    const DEFAULT_USER = 'root';
    const KEYFILE_PASSWORD = 'password';

    /**
     * Data Provider
     * Return an array of file paths to a PEM formatted pub/priv/password
     *
     * @return string[]
     */
    public function keyFileProvider()
    {
        $base = __DIR__.'/../../resources/';
        return [
            [$base.'rsa-nopw.pem.pub', $base.'rsa-nopw.pem', null],
            [$base.'rsa-pw.pem.pub', $base.'rsa-pw.pem', self::KEYFILE_PASSWORD],
            [$base.'dsa-nopw.pem.pub', $base.'dsa-nopw.pem', null],
            [$base.'dsa-pw.pem.pub', $base.'dsa-pw.pem', self::KEYFILE_PASSWORD],
        ];
    }


    /**
     * @dataProvider keyFileProvider
     * @group server
     * @medium
     */
    public function testKeyAuthentication($public, $private, $password)
    {
        $connection = new Connection(\properties::$host, \properties::$port, new KeyCredential(\properties::$user, $public, $private, $password));
        $this->assertTrue($connection->connect());
        $this->assertTrue($connection->authenticate());
        $this->assertTrue($connection->isAuthenticated());
        $connection->disconnect();
    }


}
 