<?php


namespace sinri\BinlogReader\entity\MySqlType;


class DoubleType extends BaseType
{

    /**
     * @inheritDoc
     */
    public function getValueSize($meta = null)
    {
        return 8;
    }

    /**
     * @inheritDoc
     */
    function readValueFromStream($reader, $meta = null)
    {
        $number = $reader->readNumber($this->getValueSize($meta));
        $pack = unpack('d', pack('q', $number));
        //var_dump($pack);
        return $pack[1];
    }
}