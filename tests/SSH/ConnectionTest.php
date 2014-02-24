<?php
namespace SSH;

use Logger;
use NovaTek\SSH\Connection;
use NovaTek\SSH\Credentials\PasswordCredential;
use NovaTek\SSH\Exceptions\NotConnectedException;

class ConnectionTest extends \PHPUnit_Framework_TestCase
{
    const NEW_HOST     = '127.0.0.1';
    const DEFAULT_HOST = 'localhost';
    const DEFAULT_PORT = 22;
    const BAD_FINGERPRINT = 'complete-rubbish-that-isnt-a-fingerprint';

    /**
     * @small
     */
    public function testProperties()
    {
        $connection = new Connection(self::DEFAULT_HOST);

        // Defaults
        $this->assertEquals(self::DEFAULT_HOST, $connection->getHost());
        $this->assertEquals(self::DEFAULT_PORT, $connection->getPort());
        $this->assertNull($connection->getCredentials());

        // Setters
        $credentials = new PasswordCredential('username', 'password');

        $connection->setHost(self::NEW_HOST);
        $connection->setPort('2121'); // string to int conversion here
        $connection->setCredentials($credentials);

        // Getters
        $this->assertEquals(self::NEW_HOST, $connection->getHost());
        $this->assertSame(2121, $connection->getPort());
        $this->assertTrue($connection->getCredentials() instanceof PasswordCredential);
    }

    /**
     * @small
     */
    public function testLogger()
    {
        $logger     = new Logger();
        $connection = new Connection(self::DEFAULT_HOST);
        $connection->setLogger($logger);

        try {
            $connection->authenticate(); // will fail - not connected
        } catch (NotConnectedException $e) {
        }

        $this->assertEquals("error: Cannot authenticate - not connected\n", $logger->getHistory());
    }

    /**
     * Tests the connection procedure including checking fingerprint - requires working SSH server
     *
     * @group server
     * @medium
     */
    public function testConnection()
    {
        $logger     = new Logger();
        $connection = new Connection(\properties::$host, \properties::$port);
        $connection->setLogger($logger);

        $this->assertTrue($connection->connect());
        $fp = $connection->getFingerprint();
        $this->assertEquals(32, strlen($fp)); // 32 byte MD5 HEX encoded fingerprint
        $this->assertTrue($connection->checkFingerprint($fp));
        $this->assertFalse($connection->checkFingerprint(self::BAD_FINGERPRINT));
        $this->assertNotEmpty($connection->getResource());
        $this->assertFalse($connection->isAuthenticated());

        $connection->disconnect();
        $this->assertContains('Disconnected', $logger->getHistory());
    }

    /**
     * Connects again when we already have a connection, should call disconnect() between connections
     *
     * @group server
     * @medium
     */
    public function testDoubleConnect()
    {
        $logger     = new Logger();
        $connection = new Connection(\properties::$host, \properties::$port);
        $connection->setLogger($logger);

        $this->assertTrue($connection->connect());
        $this->assertTrue($connection->isConnected());

        $this->assertTrue($connection->connect());
        $this->assertTrue($connection->isConnected());

        $this->assertContains('Disconnected', $logger->getHistory());
        $connection->disconnect();
    }

    /**
     * Connects when we already have a connection
     *
     * @group server
     * @medium
     */
    public function testBadFingerprint()
    {
        $logger     = new Logger();
        $connection = new Connection(\properties::$host, \properties::$port);
        $connection->setLogger($logger);

        $this->assertFalse($connection->connect(self::BAD_FINGERPRINT));

        $this->assertContains('Fingerprint mismatch', $logger->getHistory());
        $this->assertContains('Disconnected', $logger->getHistory());

        $this->assertFalse($connection->isConnected());

    }


    /**
     * @small
     * @expectedException \NovaTek\SSH\Exceptions\NotConnectedException
     */
    public function testOutOfOrderFingerprint()
    {
        $connection = new Connection(\properties::$host, \properties::$port);
        $connection->getFingerprint();
    }

    public function testBadConnection()
    {
        $logger     = new Logger();
        $connection = new Connection('invalid.host');
        $connection->setLogger($logger);

        $this->assertFalse($connection->connect());
        $this->assertFalse($connection->isConnected());

        $this->assertContains('Disconnected', $logger->getHistory());
    }


}
 