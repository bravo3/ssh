<?php
namespace SSH\Credentials;

use Bravo3\SSH\Connection;
use Bravo3\SSH\Credentials\PasswordCredential;

class PasswordCredentialTest extends \PHPUnit_Framework_TestCase
{

    const NEW_USERNAME     = 'username';
    const NEW_PASSWORD     = 'password';
    const DEFAULT_USERNAME = 'root';

    public function testProperties()
    {
        $credential = new PasswordCredential();

        // Defaults
        $this->assertEquals(self::DEFAULT_USERNAME, $credential->getUsername());
        $this->assertNull($credential->getPassword());

        // Getters/Setters
        $credential->setUsername(self::NEW_USERNAME);
        $credential->setPassword(self::NEW_PASSWORD);

        $this->assertEquals(self::NEW_USERNAME, $credential->getUsername());
        $this->assertEquals(self::NEW_PASSWORD, $credential->getPassword());
    }

    /**
     * @group server
     * @medium
     */
    public function testPasswordAuthentication()
    {
        $connection = new Connection(\properties::$host, \properties::$port, new PasswordCredential(\properties::$user, \properties::$pass));
        $this->assertTrue($connection->connect());
        $this->assertTrue($connection->authenticate());
        $this->assertTrue($connection->isAuthenticated());
        $connection->disconnect();
    }

}
 