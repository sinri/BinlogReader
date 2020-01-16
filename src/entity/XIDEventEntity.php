<?php


namespace sinri\BinlogReader\entity;

/**
 * Binlog::XID_EVENT
 * Transaction ID for 2PC, written whenever a COMMIT is expected.
 * @see https://dev.mysql.com/doc/internals/en/xid-event.html
 *
 * Class XIDEventEntity
 * @package sinri\BinlogReader\entity
 */
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