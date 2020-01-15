<?php


namespace sinri\BinlogReader\entity\MySqlType;


class LongLongIntType extends BaseIntegerType
{

    /**
     * @inheritDoc
     */
    protected function getByteCount()
    {
        return 8;
    }
}