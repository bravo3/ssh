<?php
namespace SSH;

use NovaTek\Component\SSH\Connection;
use NovaTek\Component\SSH\Credentials\PasswordCredential;

class ConnectionTest extends \PHPUnit_Framework_TestCase
{
    const NEW_HOST = '127.0.0.1';
    const DEFAULT_HOST = 'localhost';
    const DEFAULT_PORT = 22;

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
}
 