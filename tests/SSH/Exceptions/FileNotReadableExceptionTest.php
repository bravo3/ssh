<?php
namespace SSH\Exceptions;

use NovaTek\SSH\Exceptions\FileNotReadableException;

class FileNotReadableExceptionTest extends \PHPUnit_Framework_TestCase
{
    const FILENAME = 'filename';

    /**
     * @small
     */
    public function testProperties()
    {
        $e = new FileNotReadableException(self::FILENAME);
        $this->assertEquals(self::FILENAME, $e->getFilename());
        $this->assertEquals('The file "'.$e->getFilename().'" is not readable', $e->getMessage());
    }

}
 