<?php
namespace SSH;

use Bravo3\SSH\Connection;
use Bravo3\SSH\Credentials\PasswordCredential;

class ScpTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Get an authenticated connection ready for transaction testing
     *
     * @param bool $doAuth
     * @throws \Exception
     * @return Connection
     */
    protected function getConnection($doAuth = true)
    {
        $connection = new Connection(
            \properties::$host,
            \properties::$port,
            new PasswordCredential(\properties::$user, \properties::$pass)
        );

        if (!$connection->connect()) {
            throw new \Exception("Error connecting to test server");
        }

        if ($doAuth) {
            if (!$connection->authenticate()) {
                throw new \Exception("Error authenticating on test server");
            }
        }

        return $connection;
    }

    /**
     * @medium
     * @group        server
     * @dataProvider testFiles
     */
    public function testSendRec($fn)
    {
        $connection = $this->getConnection();

        // Test send
        $this->assertTrue($connection->scpSend(__DIR__.'/../resources/'.$fn, $fn));

        // Test receive
        $local_fn = $this->getTempDir().'ssh-test-'.rand(10000, 99999).'-'.$fn;
        $this->assertTrue($connection->scpReceive($fn, $local_fn));

        $connection->disconnect();

        // Check files are identical
        $md5_a = md5_file(__DIR__.'/../resources/'.$fn);
        $md5_b = md5_file($local_fn);

        if (file_exists($local_fn)) {
            unlink($local_fn);
        }

        $this->assertEquals($md5_a, $md5_b);
    }

    /**
     * @medium
     * @group server
     * @expectedException \Bravo3\SSH\Exceptions\NotConnectedException
     */
    public function testSendNotConnected()
    {
        $connection = $this->getConnection();
        $connection->disconnect();

        $connection->scpSend(__DIR__.'/../resources/test-file.txt', 'test-file.txt');
    }

    /**
     * @medium
     * @group server
     * @expectedException \Bravo3\SSH\Exceptions\NotConnectedException
     */
    public function testReceiveNotConnected()
    {
        $connection = $this->getConnection();
        $connection->disconnect();

        $connection->scpReceive('test-file.txt', __DIR__.'/../resources/test-file.txt');
    }

    /**
     * @medium
     * @group server
     */
    public function testMissingLocal()
    {
        $connection = $this->getConnection();
        $this->assertFalse($connection->scpSend(__DIR__.'/../resources/missing.txt', 'missing.txt'));
    }

    /**
     * @medium
     * @group server
     */
    public function testMissingRemote()
    {
        $connection = $this->getConnection();
        $local_fn   = $this->getTempDir().'ssh-test-missing.txt';
        $this->assertFalse($connection->scpReceive('missing-file.txt', $local_fn));

        if (file_exists($local_fn)) {
            unlink($local_fn);
        }
    }


    public function testFiles()
    {
        return [
            ['test-file.txt'],
            ['test-file.bin'],
        ];
    }


    protected function getTempDir()
    {
        $sys_temp = sys_get_temp_dir();
        if (substr($sys_temp, -1) != DIRECTORY_SEPARATOR) {
            $sys_temp .= DIRECTORY_SEPARATOR;
        }

        return $sys_temp;
    }

}
 