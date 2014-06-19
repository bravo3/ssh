<?php
namespace SSH\Services;

use Bravo3\SSH\Services\KeyUtility;

class KeyUtilityTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @small
     */
    public function testKeyGeneration()
    {
        $keyfile  = __DIR__.'/../../resources/rsa-pw.pem';
        $password = 'password';
        $util     = new KeyUtility();

        $pub = $util->generateSshPublicKey('file://'.$keyfile, $password);
        $this->assertEquals('ssh-rsa', substr($pub, 0, 7));
    }

    /**
     * @small
     * @expectedException \Bravo3\SSH\Exceptions\FileNotReadableException
     */
    public function testBadKeyGeneration()
    {
        $keyfile  = __DIR__.'/../../resources/test-file.bin';
        $password = 'password';
        $util     = new KeyUtility();

        $util->generateSshPublicKey('file://'.$keyfile, $password);
    }

    /**
     * @small
     * @expectedException \Bravo3\SSH\Exceptions\UnsupportedException
     */
    public function testUnknownKeyGeneration()
    {
        $keyfile  = __DIR__.'/../../resources/ecdsa-pw.pem';
        $password = 'password';
        $util     = new KeyUtility();

        $util->generateSshPublicKey('file://'.$keyfile, $password);
    }

}
 