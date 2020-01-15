<?php


namespace sinri\BinlogReader\entity\MySqlType;


use Exception;
use sinri\BinlogReader\BRByteBuffer;

abstract class BaseType
{
    /**
     * @param null|BRByteBuffer $metaBuffer
     * @param null|BRByteBuffer $buffer If needed, give the buffer where the value bytes as head
     * @return mixed
     * @throws Exception
     */
    //abstract protected function computeBytes($metaBuffer,$buffer=null);

    /**
     * @param null|BRByteBuffer $metaBuffer
     * @param BRByteBuffer $buffer
     * @param null|int $outputLength as output
     * @return mixed
     * @throws Exception
     */
    abstract public function parseValue($metaBuffer, $buffer, &$outputLength = null);
}