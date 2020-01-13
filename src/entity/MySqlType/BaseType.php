<?php


namespace sinri\BinlogReader\entity\MySqlType;


use Exception;
use sinri\BinlogReader\BinlogReader;

abstract class BaseType
{
    /**
     * @param array|int $meta
     */
    abstract public function getValueSize($meta = null);

    /**
     * @param BinlogReader $reader
     * @param array|int $meta
     * @return int[]
     * @throws Exception
     */
    public final function readBufferFromStream($reader, $meta = null)
    {
        $size = $this->getValueSize($meta);
        return $reader->readByteBuffer($size);
    }

    /**
     * @param BinlogReader $reader
     * @param array|int $meta
     * @return mixed
     * @throws Exception
     */
    abstract function readValueFromStream($reader, $meta = null);
}