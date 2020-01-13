<?php


namespace sinri\BinlogReader\entity\MySqlType;


use Exception;
use sinri\BinlogReader\BinlogReader;

class FloatType extends BaseType
{

    /**
     * @inheritDoc
     */
    public function getValueSize($meta = [])
    {
        return 4;
    }

    /**
     * @inheritDoc
     */
    function readValueFromStream($reader, $meta = [])
    {
        $number=$reader->readNumber($this->getValueSize($meta));
        return unpack('f', pack('i', $number));
    }
}