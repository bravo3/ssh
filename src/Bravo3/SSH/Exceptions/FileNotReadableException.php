<?php
namespace Bravo3\SSH\Exceptions;

class FileNotReadableException extends SSHException
{
    /**
     * @var string
     */
    protected $filename;

    /**
     * Create an exception for a missing file
     *
     * @param string     $filename
     * @param string     $msg
     * @param int        $code
     * @param \Exception $exception
     */
    function __construct($filename, $msg = null, $code = 0, \Exception $exception = null)
    {
        $this->filename = $filename;
        $msg = $msg ?: 'The file "'.$filename.'" is not readable';

        parent::__construct($msg, $code, $exception);
    }

    /**
     * Get Filename
     *
     * @return string
     */
    public function getFilename()
    {
        return $this->filename;
    }



} 