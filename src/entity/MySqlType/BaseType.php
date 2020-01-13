<?php


namespace sinri\BinlogReader\entity\MySqlType;


use Exception;
use sinri\BinlogReader\BinlogReader;

abstract class BaseType
{
    /**
     * @param array $meta
     */
    abstract public function getValueSize($meta=[]);

    /**
     * @param BinlogReader $reader
     * @param array $meta
     * @return int[]
     * @throws Exception
     */
    public final function readBufferFromStream($reader,$meta=[]){
        $size=$this->getValueSize($meta);
        return $reader->readByteBuffer($size);
    }

    /**
     * @param BinlogReader $reader
     * @param array $meta
     * @return mixed
     * @throws Exception
     */
    abstract function readValueFromStream($reader,$meta=[]);
}