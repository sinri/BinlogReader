<?php


namespace sinri\BinlogReader\entity\MySqlType;


class FloatType extends BaseType
{

    /**
     * @inheritDoc
     */
    public function getValueSize($meta = null)
    {
        return 4;
    }

    /**
     * @inheritDoc
     */
    function readValueFromStream($reader, $meta = null)
    {
        $number = $reader->readNumber($this->getValueSize($meta));
        return unpack('f', pack('i', $number));
    }
}