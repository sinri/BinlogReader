<?php


namespace sinri\BinlogReader\entity\MySqlType;


class ShortIntType extends BaseIntegerType
{

    /**
     * @inheritDoc
     */
    public function getValueSize($meta = [])
    {
        return 2;
    }
}