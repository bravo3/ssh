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
     * @group server
     * @dataProvider testFiles
     */
    public function testSendRec($fn)
    {
        $connection = $this->getConnection();

        // Test send
        $connection->scpSend(__DIR__.'/../resources/'.$fn, $fn);

        $connection->disconnect();
    }


    public function testFiles() {
        return [
          ['testfile.txt'],
          ['testfile.bin'],
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
 