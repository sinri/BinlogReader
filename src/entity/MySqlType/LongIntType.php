<?php


namespace sinri\BinlogReader\entity\MySqlType;


use Exception;
use sinri\BinlogReader\BinlogReader;

class LongIntType extends BaseIntegerType
{

    /**
     * @inheritDoc
     */
    public function getValueSize($meta = null)
    {
        return 4;
    }

    /**
     * @param BinlogReader $reader
     * @param array $meta
     * @return int|mixed
     * @throws Exception
     */
    public function readValueFromStream($reader, $meta = null)
    {
        //$reader->getLogger()->debug(__METHOD__.'@'.__LINE__,['x'=>$x]);
        return parent::readValueFromStream($reader, $meta);
    }
}