<?php


namespace sinri\BinlogReader\entity\MySqlType;


abstract class BaseIntegerType extends BaseType
{
    /**
     * @inheritDoc
     */
    function readValueFromStream($reader, $meta = null)
    {
        return $reader->readNumber($this->getValueSize($meta));
    }
}