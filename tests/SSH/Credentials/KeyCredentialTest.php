<?php
namespace SSH\Credentials;

use Bravo3\SSH\Connection;
use Bravo3\SSH\Credentials\KeyCredential;

class KeyCredentialTest extends \PHPUnit_Framework_TestCase
{
    const DEFAULT_USER = 'root';
    const NEW_USER = 'username';
    const KEYFILE_PASSWORD = 'password';
    const MISSING_FILE = 'a.missing.file';
    const FAKE_FILE = '/../../resources/not-a-file.pem';


    /**
     * @small
     * @dataProvider keyFileProvider
     */
    public function testSetKeyPair($public, $private, $password)
    {
        $credentials = new KeyCredential();

        // Defaults
        $this->assertEquals(self::DEFAULT_USER, $credentials->getUsername());

        // Via loadKeyPair
        $credentials->setKeyPair($public, $private, $password);
        $this->assertEquals($public, $credentials->getPublicKey());
        $this->assertEquals($private, $credentials->getPrivateKey());
        $this->assertEquals($password, $credentials->getPassword());
    }

    /**
     * @small
     * @dataProvider keyFileProvider
     */
    public function testProperties($public, $private, $password)
    {
        $credentials = new KeyCredential();

        // Defaults
        $this->assertEquals(self::DEFAULT_USER, $credentials->getUsername());

        // Via setters
        $credentials->setPublicKey($public);
        $credentials->setPrivateKey($private);
        $credentials->setPassword($password);

        $this->assertEquals($public, $credentials->getPublicKey());
        $this->assertEquals($private, $credentials->getPrivateKey());
        $this->assertEquals($password, $credentials->getPassword());
    }

    /**
     * @small
     * @dataProvider keyFileProvider
     * @expectedException \Bravo3\SSH\Exceptions\FileNotExistsException
     */
    public function testInvalidKey($public, $private, $password)
    {
        static $run = 0;

        if ($run++ % 2 == 0) {
            $public = self::MISSING_FILE;
        } else {
            $private = self::MISSING_FILE;
        }

        $credentials = new KeyCredential();
        $credentials->setKeyPair($public, $private, $password);
        $this->fail();
    }

    /**
     * @small
     * @dataProvider keyFileProvider
     * @expectedException \Bravo3\SSH\Exceptions\FileNotReadableException
     */
    public function testUnreadableKey($public, $private, $password)
    {
        static $run = 0;

        if ($run++ % 2 == 0) {
            $public = __DIR__.self::FAKE_FILE;
        } else {
            $private = __DIR__.self::FAKE_FILE;
        }

        $credentials = new KeyCredential();
        $credentials->setKeyPair($public, $private, $password);
        $this->fail();
    }

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
 