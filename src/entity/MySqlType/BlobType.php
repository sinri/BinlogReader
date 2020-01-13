<?php


namespace sinri\BinlogReader\entity\MySqlType;


use Exception;

class BlobType extends BaseType
{

    /**
     * @param array|int $meta
     * @return array
     */
    public function getValueSize($meta = null)
    {
        return $meta;
    }

    /**
     * @param $reader
     * @param array|int $meta bytes for size
     * @return false|string
     * @throws Exception
     */
    function readValueFromStream($reader, $meta = null)
    {
        $length = $reader->readNumber($meta);
        return $reader->readString($length);
    }
}