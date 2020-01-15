<?php


namespace sinri\BinlogReader\entity\MySqlType;


class DoubleType extends BaseType
{

    /**
     * @inheritDoc
     */
    public function parseValue($metaBuffer, $buffer, &$outputLength = null)
    {
        $outputLength = 8;
        $number = $buffer->readNumberWithSomeBytesLE(0, $outputLength);
        return unpack('d', pack('q', $number));
    }
}