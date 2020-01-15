<?php


namespace sinri\BinlogReader\entity\MySqlType;


class Int24Type extends BaseIntegerType
{

    /**
     * @inheritDoc
     */
    protected function getByteCount()
    {
        return 3;
    }
}