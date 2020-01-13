<?php


namespace sinri\BinlogReader\entity;


use sinri\BinlogReader\BinlogReader;

class IgnoredEventEntity extends BaseBinlogV4EventEntity
{
    public $mixedBody;
    /**
     * @inheritDoc
     */
    public function readFromBinlogStream($reader)
    {
        $mixLength=$this->header->eventLength-19-self::checksumByteCount();
        for($i=0;$i<$mixLength;$i++){
            $this->mixedBody[]=$reader->readNumber(1);
        }
//        $this->ignoreMessage='THIS IS A IGNORABLE EVENT!';
    }

    public function getHumanReadableDescription()
    {
        return $this->mixedBody;
    }
}