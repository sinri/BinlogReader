<?php


namespace sinri\BinlogReader\entity\MySqlType;


class LongLongIntType extends BaseIntegerType
{

    /**
     * @inheritDoc
     */
    public function getValueSize($meta = null)
    {
        return 8;
    }
}