<?php


namespace sinri\BinlogReader\entity\MySqlType;


class LongIntType extends BaseIntegerType
{

    /**
     * @inheritDoc
     */
    public function getValueSize($meta = [])
    {
        return 4;
    }
}