<?php
namespace SSH;

use NovaTek\SSH\Connection;
use NovaTek\SSH\Credentials\PasswordCredential;
use NovaTek\SSH\ExecutionStream;
use NovaTek\SSH\Terminal;

class ExecutionStreamTest extends \PHPUnit_Framework_TestCase
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
     * @expectedException \NovaTek\SSH\Exceptions\NotAuthenticatedException
     * @group server
     */
    public function testNoAuth()
    {
        $connection = $this->getConnection(false);

        $exec = new ExecutionStream('echo "hello world"', $connection, new Terminal());
        $exec->getOutput();

        $this->fail();
    }

    /**
     * @medium
     * @group server
     */
    public function testOutput()
    {
        $connection = $this->getConnection();
        $exec       = $connection->execute('whoami');
        $this->assertContains(\properties::$user, $exec->getOutput());
        $connection->disconnect();
    }

    /**
     * @medium
     * @group server
     */
    public function testOutputWithError()
    {
        $connection = $this->getConnection();
        $exec       = $connection->execute('echo "stderr" 1>&2; echo "stdout"');

        $out = $exec->getOutput();
        $this->assertContains("stdout", $out);
        $this->assertNotContains("stderr", $out);

        $connection->disconnect();
    }

    /**
     * @medium
     * @group server
     */
    public function testSegmentedOutput()
    {
        $connection = $this->getConnection();
        $exec       = $connection->execute('echo "stderr" 1>&2; echo "stdout"');

        $out = $exec->getSegmentedOutput();

        $this->assertContains("stderr", $out['stderr']);
        $this->assertContains("stdout", $out['stdout']);

        $connection->disconnect();
    }

    /**
     * @medium
     * @expectedException \NovaTek\SSH\Exceptions\StreamNotOpenException
     * @group server
     */
    public function testDoubleRead()
    {
        $connection = $this->getConnection();
        $exec       = $connection->execute('echo "hello world"');

        $this->assertTrue($exec->isOpen());
        $this->assertContains("hello world", $exec->getOutput());
        $this->assertFalse($exec->isOpen());
        $exec->getSegmentedOutput(); // throw exception here

        $this->fail();
    }

    /**
     * @medium
     * @group server
     */
    public function testMultipleExecutions()
    {
        $connection = $this->getConnection();

        $exec1 = $connection->execute('echo "hello world"');
        $this->assertContains("hello world", $exec1->getOutput());

        $exec2 = $connection->execute('echo "hiya"');
        $this->assertContains("hiya", $exec2->getOutput());

        $connection->disconnect();
    }

    /**
     * @medium
     * @group server
     */
    public function testMultipleExecutionsWithoutReading()
    {
        $connection = $this->getConnection();

        $connection->execute('echo "hello world"');
        $exec = $connection->execute('echo "hiya"');

        $out = $exec->getOutput();

        $this->assertNotContains("hello world", $out);
        $this->assertContains("hiya", $out);

        $connection->disconnect();
    }

    /**
     * @medium
     * @expectedException \NovaTek\SSH\Exceptions\NotConnectedException
     * @group server
     */
    public function testDisconnectionBetweenExecutions()
    {
        $connection = $this->getConnection();

        $exec = $connection->execute('echo "hello world"');
        $this->assertContains("hello world", $exec->getOutput());

        $connection->disconnect();

        $connection->execute('echo "whoa"'); // exception here

        $this->fail();
    }


}
 