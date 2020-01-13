<?php


namespace sinri\BinlogReader\entity\MySqlType;


class YearType extends BaseIntegerType
{

    /**
     * @inheritDoc
     */
    public function getValueSize($meta = [])
    {
        return 2;
    }
}