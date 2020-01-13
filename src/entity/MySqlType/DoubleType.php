<?php


namespace sinri\BinlogReader\entity\MySqlType;


use Exception;
use sinri\BinlogReader\BinlogReader;

class DoubleType extends BaseType
{

    /**
     * @inheritDoc
     */
    public function getValueSize($meta = [])
    {
        return 8;
    }

    /**
     * @inheritDoc
     */
    function readValueFromStream($reader, $meta = [])
    {
        $number=$reader->readNumber($this->getValueSize($meta));
        return unpack('d', pack('i', $number));
    }
}