<?php


namespace sinri\BinlogReader\entity\MySqlType;


class TinyIntType extends BaseIntegerType
{

    /**
     * @inheritDoc
     */
    protected function getByteCount()
    {
        return 1;
    }
}