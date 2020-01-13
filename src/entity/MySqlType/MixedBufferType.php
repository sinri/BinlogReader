<?php


namespace sinri\BinlogReader\entity\MySqlType;


use Exception;
use sinri\ark\core\ArkHelper;
use sinri\BinlogReader\BinlogReader;

class MixedBufferType extends BaseType
{

    protected $size=null;
    protected $buffer=null;

    /**
     * @param BinlogReader $reader
     * @throws Exception
     */
    protected function read($reader){
        $this->size=$reader->readLenencInt();
        $this->buffer=$reader->readByteBuffer($this->size);
    }

    /**
     * @param BinlogReader[] $meta [reader]
     * @return int
     * @throws Exception
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
     * @throws Exception
     */
    function readValueFromStream($reader, $meta = [])
    {
        if($this->size===null) {
            $this->read($reader);
        }
        return $this->buffer;
    }

    public final function readByteInBuffer($index,$default=0){
        return ArkHelper::readTarget($this->buffer,[$index],$default);
    }
}