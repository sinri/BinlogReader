<?php


namespace sinri\BinlogReader\entity\MySqlType;


class SetType extends BaseType
{

    /**
     * @inheritDoc
     */
    public function parseValue($metaBuffer, $buffer, &$outputLength = null)
    {
        $outputLength = $metaBuffer->readNumberWithSomeBytesLE(1, 1);
        return $buffer->readNumberWithSomeBytesLE(0, $outputLength);
    }
}