<?php


namespace sinri\BinlogReader\entity\MySqlType;


class FloatType extends BaseType
{

    /**
     * @inheritDoc
     */
    public function parseValue($metaBuffer, $buffer, &$outputLength = null)
    {
        $outputLength = 4;
        $number = $buffer->readNumberWithSomeBytesLE(0, $outputLength);
        $pack = unpack('f', pack('i', $number));
        return $pack[1];
    }
}