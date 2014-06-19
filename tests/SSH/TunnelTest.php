<?php
namespace SSH;

use Bravo3\SSH\Connection;
use Bravo3\SSH\Credentials\KeyCredential;
use Bravo3\SSH\Credentials\PasswordCredential;

class TunnelTest extends \PHPUnit_Framework_TestCase
{
    const KEYFILE_PASSWORD = 'password';

    /**
     * @group server
     * @medium
     */
    public function testTunnelKeypair()
    {
        $base       = __DIR__.'/../resources/';
        $connection = new Connection(
            \properties::$host, \properties::$port,
            new KeyCredential(\properties::$user, null, $base.'rsa-nopw.pem')
        );

        $this->assertTrue($connection->connect());
        $this->assertTrue($connection->authenticate());
        $this->assertTrue($connection->isAuthenticated());

        // Should be able to tunnel to yourself
        $tunnel = $connection->tunnel(
            \properties::$host,
            \properties::$port,
            new KeyCredential(\properties::$user, null, $base.'rsa-nopw.pem')
        );

        $this->assertTrue($tunnel->connect());
        $this->assertTrue($tunnel->authenticate());
        $this->assertTrue($tunnel->isAuthenticated());

        $tunnel->disconnectChain();

        $this->assertFalse($tunnel->isConnected());
        $this->assertFalse($connection->isConnected());
    }

    /**
     * @group server
     * @medium
     */
    public function testTunnelPassword()
    {
        $connection = new Connection(
            \properties::$host, \properties::$port,
            new PasswordCredential(\properties::$user, \properties::$pass)
        );

        $this->assertTrue($connection->connect());
        $this->assertTrue($connection->authenticate());
        $this->assertTrue($connection->isAuthenticated());

        // Should be able to tunnel to yourself
        $tunnel = $connection->tunnel(
            \properties::$host, \properties::$port,
            new PasswordCredential(\properties::$user, \properties::$pass)
        );

        $this->assertTrue($tunnel->connect());
        $this->assertTrue($tunnel->authenticate());
        $this->assertTrue($tunnel->isAuthenticated());

        $tunnel->disconnectChain();

        $this->assertFalse($tunnel->isConnected());
        $this->assertFalse($connection->isConnected());
    }

    /**
     * @group server
     * @medium
     */
    public function testBadTunnel()
    {
        $connection = new Connection(
            \properties::$host, \properties::$port,
            new PasswordCredential(\properties::$user, \properties::$pass)
        );

        $this->assertTrue($connection->connect());
        $this->assertTrue($connection->authenticate());
        $this->assertTrue($connection->isAuthenticated());

        // Should be able to tunnel to yourself
        $tunnel = $connection->tunnel(
            \properties::$host, '23433',
            new PasswordCredential(\properties::$user, \properties::$pass)
        );

        $this->assertNull($tunnel);

    }

    /**
     * @group server
     * @expectedException \Bravo3\SSH\Exceptions\NotAuthenticatedException
     * @medium
     */
    public function testParentNotAuthenticated()
    {
        $connection = new Connection(
            \properties::$host, \properties::$port,
            new PasswordCredential(\properties::$user, \properties::$pass)
        );

        $this->assertTrue($connection->connect());

        // Should be able to tunnel to yourself
        $connection->tunnel(
            \properties::$host, \properties::$port,
            new PasswordCredential(\properties::$user, \properties::$pass)
        );

        $this->fail();

    }


}
 