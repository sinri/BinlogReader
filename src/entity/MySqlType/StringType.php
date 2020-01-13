<?php


namespace sinri\BinlogReader\entity\MySqlType;


use Exception;
use sinri\BinlogReader\BinlogReader;

class StringType extends BaseType
{
    protected $size=null;
    protected $string=null;

    /**
     * @param BinlogReader $reader
     */
    protected function read($reader){
        $this->size=$reader->readLenencInt();
        $this->string=$reader->readString($this->size);
    }

    /**
     * @param BinlogReader[] $meta [reader]
     * @return int
     */
    public function getValueSize($meta = [])
    {
        if($this->size===null) {
            $reader = $meta[0];
            $this->read($reader);
        }
        return $this->size;
    }

    /**
     * @param BinlogReader $reader
     * @param array $meta
     * @return string
     */
    function readValueFromStream($reader, $meta = [])
    {
        if($this->size===null) {
            $this->read($reader);
        }
        return $this->string;
    }
}