<?php


namespace sinri\BinlogReader\entity\MySqlType;


class VarcharType extends BaseType
{
    /**
     * @inheritDoc
     */
    public function parseValue($metaBuffer, $buffer, &$outputLength = null)
    {
        $lengthBytes = ($metaBuffer->readNumberWithSomeBytesLE(0) <= 255 ? 1 : 2);
        $contentLength = $buffer->readNumberWithSomeBytesLE(0, $lengthBytes);
        $outputLength = $contentLength + $lengthBytes;
        if ($contentLength <= 0) return "";
        return $buffer->readString($lengthBytes, $contentLength);
    }
}