<?php
namespace SSH;

use Bravo3\SSH\Connection;
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
        $this->assertContains('-'.\properties::$user.'-', $shell->waitForContent()->getAll());
    }

    /**
     * @medium
     * @group  server
     */
    public function testSmartConsole()
    {
        $shell = $this->getShell();
        $shell_type = $shell->getShellType();
        $this->assertFalse($shell_type == ShellType::UNKNOWN());

        // Test for command result, trimmed
        $this->assertEquals('-'.\properties::$user.'-', $shell->sendSmartCommand("echo -`whoami`-")->getAll());

        // Test chaining commands works, no trimming
        $response = $shell->sendSmartCommand("ls -lah", false);
        $all = $response->getAll();
        $this->assertNotEmpty($all);
        $this->assertContains('ls -lah', $all); // should contain command echo
        $this->assertContains($shell->getSmartMarker(), $all); // should contain PS1 marker
    }

    /**
     * @medium
     * @group server
     */
    public function testRegex()
    {
        $shell = $this->getShell();
        $this->assertNotEmpty($shell->waitForContent()->getAll());

        $shell->sendln("echo -`whoami`-");
        $response = $shell->readUntilExpression('/\-'.\properties::$user.'\-/i');
        $this->assertContains('-'.\properties::$user.'-', $response->getAll());

        // should contain echo/PS1
        $this->assertGreaterThan(strlen(\properties::$user) + 2, strlen($response->getAll()));
    }

    /**
     * @medium
     * @group server
     */
    public function testMarker()
    {
        $shell = $this->getShell();
        $this->assertNotEmpty($shell->waitForContent()->getAll());

        $shell->sendln("echo -`whoami`-");
        $response = $shell->readUntilMarker('-'.\properties::$user.'-');
        $this->assertContains('-'.\properties::$user.'-', $response->getAll());

        // should contain echo/PS1
        $this->assertGreaterThan(strlen(\properties::$user) + 2, strlen($response->getAll()));
    }

    /**
     * @medium
     * @group server
     */
    public function testStdErr()
    {
        $shell = $this->getShell();
        $this->assertNotEmpty($shell->waitForContent(1)->getAll());

        $shell->sendln("rm fakefile");
        $response = $shell->readUntilPause(0.5);

        echo "\n";
        foreach ($response as $line) {
            $data = str_replace("\r\n", "\\n", $line[1]);
            $data = str_replace("\n", "\\n", $data);
            echo (($line[0] == 0) ? '| ' : '+ ').$data."\n";
        }

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



}
 