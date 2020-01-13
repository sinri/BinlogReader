<?php


namespace sinri\BinlogReader\entity\MySqlType;


use Exception;
use sinri\BinlogReader\BinlogReader;

class VarcharType extends StringType
{
    /**
     * @param BinlogReader $reader
     * @throws Exception
     */
    protected function read($reader)
    {
        $this->size = $reader->readLenencInt() + 1;
        $reader->readNumber(1); // first byte would be eaten
        $this->string = $reader->readString($this->size - 1);
    }

}