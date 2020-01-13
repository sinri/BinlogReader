<?php


namespace sinri\BinlogReader\entity\MySqlType;


class Int24Type extends BaseIntegerType
{

    /**
     * @inheritDoc
     */
    public function getValueSize($meta = null)
    {
        return 3;
    }
}