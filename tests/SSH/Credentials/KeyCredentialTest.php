<?php
namespace SSH\Credentials;

use NovaTek\Component\SSH\Credentials\KeyCredential;

class KeyCredentialTest extends \PHPUnit_Framework_TestCase
{
    const DEFAULT_USER = 'root';
    const NEW_USER = 'username';
    const KEYFILE_PASSWORD = 'password';


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
     * @dataProvider keyFileNotExistsProvider
     * @expectedException \NovaTek\Component\SSH\Exceptions\FileNotExistsException
     */
    public function testInvalidFile($public, $private, $password)
    {
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
     * Data Provider
     * Return an array of files that do not exist :)
     *
     * @return string[]
     */
    public function keyFileNotExistsProvider()
    {
        $base = __DIR__.'/../../resources/';
        return [
            [$base.'invalid.pem.pub', $base.'invalid.pem', null],
        ];
    }

}
 