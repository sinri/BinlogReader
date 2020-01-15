<?php


namespace sinri\BinlogReader\entity\MySqlType;


class LongIntType extends BaseIntegerType
{

    /**
     * @inheritDoc
     */
    protected function getByteCount()
    {
        return 4;
    }
}