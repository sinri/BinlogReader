<?php


namespace sinri\BinlogReader\entity\MySqlType;


class StringType extends BaseType
{

    /**
     * thanks to [noplay]
     * @see https://github.com/noplay/python-mysql-replication/blob/master/pymysqlreplication/column.py
     * @inheritDoc
     */
    public function parseValue($metaBuffer, $buffer, &$outputLength = null)
    {
        $t = $metaBuffer->readNumberWithSomeBytesBE(0, 2);
        $maxCharCount = ((($t >> 4) & 0x0300) ^ 0x0300) + ($t & 0x00ff);

        $lengthBytes = ($maxCharCount <= 255 ? 1 : 2);
        $contentLength = $buffer->readNumberWithSomeBytesLE(0, $lengthBytes);
        $outputLength = $contentLength + $lengthBytes;
        if ($contentLength <= 0) return "";
        return $buffer->readString($lengthBytes, $contentLength);
    }
}