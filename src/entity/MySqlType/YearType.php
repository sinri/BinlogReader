<?php


namespace sinri\BinlogReader\entity\MySqlType;


class YearType extends BaseIntegerType
{

    /**
     * @inheritDoc
     */
    protected function getByteCount()
    {
        return 2;
    }
}