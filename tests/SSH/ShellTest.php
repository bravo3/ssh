<?php
namespace SSH;

use Bravo3\SSH\Connection;
use Bravo3\SSH\Credentials\KeyCredential;
use Bravo3\SSH\Credentials\PasswordCredential;
use Bravo3\SSH\Enum\ShellType;
use Bravo3\SSH\Shell;
use Bravo3\SSH\Terminal;

class ShellTest extends \PHPUnit_Framework_TestCase
{

    /**
     * Get an authenticated connection ready for transaction testing
     *
     * @param bool $doAuth
     * @throws \Exception
     * @return Shell
     */
    protected function getShell()
    {
        $connection = new Connection(
            \properties::$host,
            \properties::$port,
            new PasswordCredential(\properties::$user, \properties::$pass)
        );

        if (!$connection->connect()) {
            throw new \Exception("Error connecting to test server");
        }

        if (!$connection->authenticate()) {
            throw new \Exception("Error authenticating on test server");
        }

        return $connection->getShell();
    }



    /**
     * @small
     * @group  server
     */
    public function testBasicReadWrite()
    {
        $shell = $this->getShell();
        $this->assertNotEmpty($shell->getResource());

        $this->assertNotEmpty($shell->waitForContent());

        $shell->sendln("echo -`whoami`-");
        $this->assertContains('-'.\properties::$user.'-', $shell->waitForContent());
    }

    /**
     * @medium
     * @group  server
     */
    public function testSmartConsole()
    {
        $shell = $this->getShell();

        // Test for command result, trimmed
        $this->assertEquals('-'.\properties::$user.'-', $shell->sendSmartCommand("echo -`whoami`-"));

        // Test chaining commands works, no trimming
        $response = $shell->sendSmartCommand("ls -lah", false);
        $this->assertNotEmpty($response);
        $this->assertContains('ls -lah', $response); // should contain command echo
        $this->assertContains($shell->getSmartMarker(), $response); // should contain PS1 marker
    }

    /**
     * @medium
     * @group server
     */
    public function testRegex()
    {
        $shell = $this->getShell();
        $this->assertNotEmpty($shell->waitForContent());

        $shell->sendln("echo -`whoami`-");
        $response = $shell->readUntilExpression('/\-'.\properties::$user.'\-/i');
        $this->assertContains('-'.\properties::$user.'-', $response);
        $this->assertGreaterThan(strlen(\properties::$user) + 2, strlen($response));    // should contain echo/PS1
    }

    /**
     * @medium
     * @group server
     */
    public function testMarker()
    {
        $shell = $this->getShell();
        $this->assertNotEmpty($shell->waitForContent());

        $shell->sendln("echo -`whoami`-");
        $response = $shell->readUntilMarker('-'.\properties::$user.'-');
        $this->assertContains('-'.\properties::$user.'-', $response);
        $this->assertGreaterThan(strlen(\properties::$user) + 2, strlen($response));    // should contain echo/PS1

    }

    /**
     * @medium
     * @expectedException \Bravo3\SSH\Exceptions\NotConnectedException
     * @group server
     */
    public function testNotConnected()
    {
        $connection = new Connection(
            \properties::$host,
            \properties::$port,
            new PasswordCredential(\properties::$user, \properties::$pass)
        );

        $shell = new Shell($connection, new Terminal());    // exception here
        $shell->sendln("hello");

        $this->fail();
    }

    /**
     * @medium
     * @expectedException \Bravo3\SSH\Exceptions\NotAuthenticatedException
     * @group server
     */
    public function testNoAuth()
    {
        $connection = new Connection(
            \properties::$host,
            \properties::$port,
            new PasswordCredential(\properties::$user, \properties::$pass)
        );

        if (!$connection->connect()) {
            throw new \Exception("Error connecting to test server");
        }

        $shell = new Shell($connection, new Terminal());    // exception here
        $shell->sendln("hello");

        $this->fail();
    }

    /**
     * @medium
     * @group server
     */
    public function testShellDetection()
    {
        $shell = $this->getShell();
        $type = $shell->getShellType(3);
        $this->assertFalse($type == ShellType::UNKNOWN());
    }



}
 