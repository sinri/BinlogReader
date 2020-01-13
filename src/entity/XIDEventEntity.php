<?php


namespace sinri\BinlogReader\entity;


class XIDEventEntity extends BaseBinlogV4EventEntity
{

    protected $xid;

    public function getHumanReadableDescription()
    {
        return 'COMMIT -> XID: ' . $this->xid;
    }

    /**
     * @inheritDoc
     */
    public function readFromBinlogStream($reader)
    {
        $this->xid = $reader->readNumber(8);
    }
}