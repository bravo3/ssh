<?php
namespace SSH\Exceptions;

use Bravo3\SSH\Exceptions\FileNotExistsException;

class FileNotExistsExceptionTest extends \PHPUnit_Framework_TestCase
{
    const FILENAME = 'filename';

    /**
     * @small
     */
    public function testProperties()
    {
        $e = new FileNotExistsException(self::FILENAME);
        $this->assertEquals(self::FILENAME, $e->getFilename());
        $this->assertEquals('The file "'.$e->getFilename().'" does not exist', $e->getMessage());
    }

}
 