<?php


namespace sinri\BinlogReader\entity;


use sinri\BinlogReader\BinlogReader;
use sinri\BinlogReader\BRKit;

class GTIDEventEntity extends BaseBinlogV4EventEntity
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
    }

    public function getHumanReadableDescription()
    {
        return 'Mixed Body: '.PHP_EOL.BRKit::hexMatrixNumbers($this->mixedBody);
    }
}