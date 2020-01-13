<?php


namespace sinri\BinlogReader\entity\MySqlType;


class YearType extends BaseIntegerType
{

    /**
     * @inheritDoc
     */
    public function getValueSize($meta = null)
    {
        return 2;
    }
}