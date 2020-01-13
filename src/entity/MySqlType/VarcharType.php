<?php


namespace sinri\BinlogReader\entity\MySqlType;


class VarcharType extends BaseType
{
    protected $charBytesCount = 3;
    protected $lengthBytes = null;

    public function __construct($charBytesCount)
    {
        $this->charBytesCount = $charBytesCount;
    }

//    /**
//     * @param BinlogReader $reader
//     * @throws Exception
//     */
//    protected function read($reader)
//    {
//
//
//        $this->size = $reader->readLenencInt() + 1;
//        $reader->readNumber(1); // first byte would be eaten
//        $this->string = $reader->readString($this->size - 1);
//    }

    /**
     * @param array|int $meta
     * @return float|int
     */
    public function getValueSize($meta = null)
    {
//        if($this->lengthBytes===null) {
//            // only for UTF8
//            $maxLength = ($meta / $this->charBytesCount)+1;
//            $bytes = 0;
//            while ($maxLength > 0) {
//                $bytes++;
//                $maxLength = floor($maxLength / 256);
//            }
//            $this->lengthBytes = $bytes;
//            var_dump([$maxLength,$this->lengthBytes]);
//        }

        if ($this->lengthBytes === null) {
            $this->lengthBytes = ($meta <= 255 ? 1 : 2);
        }

        return $this->lengthBytes;
    }

    /**
     * @inheritDoc
     */
    function readValueFromStream($reader, $meta = null)
    {
        $length = $reader->readNumber($this->getValueSize($meta));
        if ($length <= 0) return "";
        return $reader->readString($length);
    }
}