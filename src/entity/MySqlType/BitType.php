<?php


namespace sinri\BinlogReader\entity\MySqlType;


class BitType extends BaseType
{
    protected $bits;
    protected $bytes;
    protected $buffer;

    /**
     * @inheritDoc
     */
    public function parseValue($metaBuffer, $buffer, &$outputLength = null)
    {
        $bits = $metaBuffer->readNumberWithSomeBytesLE(0, 1);
        $bytes = $metaBuffer->readNumberWithSomeBytesLE(1, 1);

        $this->bits = ($bytes * 8) + $bits;
        $this->bytes = ($this->bits + 7) >> 3;
        $outputLength = $this->bytes;
        $this->buffer = $buffer->getSubByteBuffer(0, $this->bytes);

        return $this->buffer;
    }
}