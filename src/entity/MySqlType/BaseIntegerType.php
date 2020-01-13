<?php


namespace sinri\BinlogReader\entity\MySqlType;


use Exception;
use sinri\BinlogReader\BinlogReader;

abstract class BaseIntegerType extends BaseType
{
    /**
     * @inheritDoc
     */
    function readValueFromStream($reader, $meta = [])
    {
        return $reader->readNumber($this->getValueSize($meta));
    }
}