<?php
namespace SSH\Credentials;

use NovaTek\Component\SSH\Credentials\KeyCredential;

class KeyCredentialTest extends \PHPUnit_Framework_TestCase
{
    const DEFAULT_USER = 'root';
    const NEW_USER = 'username';

    /**
     * @small
     * @dataProvider keyProvider
     */
    public function testKey($key)
    {
        $credentials = new KeyCredential();

        // Defaults
        $this->assertEquals(self::DEFAULT_USER, $credentials->getUsername());

        // Getters/setters
        $credentials->setUsername(self::NEW_USER);
        $this->assertEquals(self::NEW_USER, $credentials->getUsername());

        $credentials->setKey($key);
        $this->assertEquals($key, $credentials->getKey());
    }


    /**
     * @small
     * @dataProvider keyFileProvider
     */
    public function testFromFile($fn)
    {
        $this->assertFileExists($fn);
        $credentials = new KeyCredential();

        $credentials->loadFromKeyfile($fn);
        $this->assertEquals(file_get_contents($fn), $credentials->getKey());
    }

    /**
     * @small
     * @dataProvider keyFileNotExistsProvider
     * @expectedException \NovaTek\Component\SSH\Exceptions\FileNotExistsException
     */
    public function testInvalidFile($fn)
    {
        $credentials = new KeyCredential();
        $credentials->loadFromKeyfile($fn);
        $this->fail();
    }


    /**
     * Data Provider
     * Return an array of PEM formatted private keys
     *
     * @return string[]
     */
    public function keyProvider()
    {
        return [['insert private key here']];
    }

    /**
     * Data Provider
     * Return an array of file paths to a PEM formatted private key
     *
     * @return string[]
     */
    public function keyFileProvider()
    {
        return [[__DIR__.'/../../resources/private-key-rsa.pem']];
    }

    /**
     * Data Provider
     * Return an array of files that do not exist :)
     *
     * @return string[]
     */
    public function keyFileNotExistsProvider()
    {
        return [['not-a-valid-filename.pem']];
    }

}
 