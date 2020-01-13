<?php


namespace sinri\BinlogReader\entity\MySqlType;


class TinyIntType extends BaseIntegerType
{

    /**
     * @inheritDoc
     */
    public function getValueSize($meta = null)
    {
        return 1;
    }
}