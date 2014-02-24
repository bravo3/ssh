<?php
namespace SSH;

use NovaTek\SSH\Connection;
use NovaTek\SSH\Credentials\PasswordCredential;

class ExecutionStreamTest extends \PHPUnit_Framework_TestCase
{

    /**
     * Get an authenticated connection ready for transaction testing
     *
     * @return Connection
     * @throws \Exception
     */
    protected function getConnection()
    {
        $connection = new Connection(\properties::$host, \properties::$port, new PasswordCredential(\properties::$user, \properties::$pass));

        if (!$connection->connect()) {
            throw new \Exception("Error connecting to test server");
        }

        if (!$connection->authenticate()) {
            throw new \Exception("Error authenticating on test server");
        }

        return $connection;
    }

    /**
     * @medium
     */
    public function testOutput()
    {
        $connection = $this->getConnection();
        $exec = $connection->execute('whoami');
        $this->assertContains(\properties::$user, $exec->getOutput());
        $connection->disconnect();
    }

}
 