<?php


namespace sinri\BinlogReader\entity\MySqlType;


class TinyIntType extends BaseIntegerType
{

    /**
     * @inheritDoc
     */
    public function getValueSize($meta = [])
    {
        return 1;
    }
}