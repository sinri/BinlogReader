<?php


namespace sinri\BinlogReader\entity\MySqlType;


class BlobType extends BaseType
{

    /**
     * @inheritDoc
     */
    public function parseValue($metaBuffer, $buffer, &$outputLength = null)
    {
        $lengthByteCount = $metaBuffer->readNumberWithSomeBytesLE(0);
        $contentLength = $buffer->readNumberWithSomeBytesLE(0, $lengthByteCount);
        $outputLength = $lengthByteCount + $contentLength;
        return $buffer->readString($lengthByteCount, $contentLength);
    }
}