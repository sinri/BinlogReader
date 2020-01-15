<?php


namespace sinri\BinlogReader\entity\MySqlType;


class ShortIntType extends BaseIntegerType
{

    /**
     * @inheritDoc
     */
    protected function getByteCount()
    {
        return 2;
    }
}