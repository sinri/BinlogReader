<?php


namespace sinri\BinlogReader\entity\MySqlType;


class StringType extends BaseType
{

    /**
     * @inheritDoc
     */
    public function parseValue($metaBuffer, $buffer, &$outputLength = null)
    {
        $contentByteCount = $buffer->readLenencInt(0, $lengthByteCount);
        $outputLength = $contentByteCount + $lengthByteCount;
        return $buffer->readString($lengthByteCount, $contentByteCount);
    }
}