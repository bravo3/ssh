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

    /**
     * @var array[]
     */
    protected $buffer = [];

    /**
     * @var int
     */
    protected $total_count = 0;

    /**
     * @var array<int, int>
     */
    protected $channel_count = [];

    /**
     * @var string|null A buffer of combined content as a single string, or null if the feature is disabled
     */
    protected $full_buffer = null;

    /**
     * @param bool $full_buffer Maintain a buffer of the full content - uses more memory but improves performance
     */
    function __construct($buffer = [], $full_buffer = true)
    {
        $this->buffer = $buffer;

        if ($full_buffer) {
            $this->full_buffer = '';

            if ($buffer) {
                foreach ($buffer as $line) {
                    $this->full_buffer .= $line[1];
                }
            }
        }
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
        if (!isset($this->channel_count[$channel])) {
            $this->channel_count[$channel] = strlen($data);
        } else {
            $this->channel_count[$channel] += strlen($data);
        }
        $this->total_count += strlen($data);

        if ($this->full_buffer !== null) {
            $this->full_buffer .= $data;
        }

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
        if ($this->full_buffer !== null) {
            return $this->full_buffer;
        }

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

    /**
     * Get the size of all channels
     *
     * @return int
     */
    public function getCombinedSize()
    {
        return $this->total_count;
    }

    /**
     * Get the size of a given channel
     *
     * @param int $channel
     * @return int
     */
    public function getChannelSize($channel)
    {
        return isset($this->channel_count[$channel]) ? $this->channel_count[$channel] : 0;
    }

    public function __toString()
    {
        return $this->getAll();
    }

    /**
     * Get raw buffer
     *
     * @return array[]
     */
    public function getBuffer()
    {
        return $this->buffer;
    }

}
 