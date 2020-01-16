<?php


namespace sinri\BinlogReader\entity;


class GTIDEventEntity extends BaseEventEntity
{
    protected $flag;
    protected $nextGTID = null;

    /**
     * @inheritDoc
     */
    public function getHumanReadableDescription()
    {
        //return 'Mixed Body: ' . PHP_EOL . $this->bodyBuffer->showAsHexMatrix();
        $string = "GTID Flag " . $this->flag . PHP_EOL;
        $string .= "SET @@SESSION.GTID_NEXT= '{$this->nextGTID}';" . PHP_EOL;
        return $string;
    }

    /**
     * @inheritDoc
     */
    public function parseBodyBuffer()
    {
        // know nothing about it, let the body data as is
        // 0x01, need to confirm what does this mean
        // 0xca,0x26,0x64,0x1b, // 4
        // 0xc7,0x72, // 2
        // 0x11,0xe7, // 2
        // 0xac,0xc2, // 2
        // 0x7c,0xd3,0x0a,0xe4,0x39,0x2a, // 6
        // 0x5f,0x5a,0x85,0x81,0x00,0x00,0x00,0x00,

        // SET @@SESSION.GTID_NEXT= 'ca26641b-c772-11e7-acc2-7cd30ae4392a:2173000287'/*!*/;

        // I am not sure the count here
        $this->flag = $this->bodyBuffer->readNumberWithSomeBytesLE(0, 1);
        $offset = 1;

        $sub = $this->bodyBuffer->getSubByteBuffer($offset, 16);
        $offset += 16;
        $gtid = $sub->showAsHexIDString([4, 2, 2, 2, 6], '-') . ':' . $this->bodyBuffer->readNumberWithSomeBytesLE($offset, 8);
        $offset += 8;
        $this->nextGTID = $gtid;
    }

}