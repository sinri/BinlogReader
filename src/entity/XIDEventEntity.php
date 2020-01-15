<?php


namespace sinri\BinlogReader\entity;


class XIDEventEntity extends BaseEventEntity
{
    public $xid;

    /**
     * @inheritDoc
     */
    public function getHumanReadableDescription()
    {
        return 'COMMIT -> XID: ' . $this->xid;
    }

    /**
     * @inheritDoc
     */
    public function parseBodyBuffer()
    {
        $this->xid = $this->bodyBuffer->readNumberWithSomeBytesLE(0, 8);//$reader->readNumber(8);
    }
}