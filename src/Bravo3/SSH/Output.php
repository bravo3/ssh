<?php
namespace Bravo3\SSH;

/**
 * Structured output buffer
 *
 * - If this class is typecast to a string it will return a string of all channels in order
 * - If you iterate over this class, it returns an array of [$channel, $data]
 *
 * Typically an object of this class will contain stdout in channel 0, and stderr in channel 1.
 */
class Output implements \IteratorAggregate
{

    protected $buffer = [];

    function __construct(array $buffer = [])
    {
        $this->buffer = $buffer;
    }

    /**
     * Get an iterator
     *
     * @return \Traversable
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->buffer);
    }

    /**
     * Add data to a channel
     *
     * @param int    $channel
     * @param string $data
     */
    public function add($channel, $data)
    {
        $this->buffer[] = [$channel, $data];
    }

    /**
     * Add a line of text to a channel
     *
     * @param int    $channel
     * @param string $data
     */
    public function addln($channel, $data)
    {
        $this->add($channel, $data."\n");
    }

    /**
     * Get all data in a single string
     *
     * @return string
     */
    public function getAll()
    {
        $out = '';
        foreach ($this->buffer as $record) {
            $out .= $record[1];
        }
        return $out;
    }

    /**
     * Get all data for a channel
     *
     * @param $channel
     * @return string
     */
    public function getChannel($channel)
    {
        $out = '';
        foreach ($this->buffer as $record) {
            if ($record[0] === $channel) {
                $out .= $record[1];
            }
        }
        return $out;
    }

    public function __toString()
    {
        return $this->getAll();
    }

}
 