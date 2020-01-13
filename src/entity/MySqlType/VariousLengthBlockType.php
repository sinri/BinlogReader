<?php


namespace sinri\BinlogReader\entity\MySqlType;


use Exception;
use sinri\BinlogReader\BinlogReader;

class VariousLengthBlockType extends BaseType
{

    /**
     * @inheritDoc
     */
    public function getValueSize($meta = [])
    {
        // TODO: Implement getValueSize() method.
    }

    /**
     * @inheritDoc
     */
    function readValueFromStream($reader, $meta = [])
    {
        // TODO: Implement readValueFromStream() method.
    }
}