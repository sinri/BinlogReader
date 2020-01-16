<?php


namespace sinri\BinlogReader\entity;

/**
 * Binlog::ROTATE_EVENT
 * The rotate event is added to the binlog as last event to tell the reader what binlog to request next.
 * @see https://dev.mysql.com/doc/internals/en/rotate-event.html
 *
 * Class RotateEventEntity
 * @package sinri\BinlogReader\entity
 */
class RotateEventEntity extends BaseEventEntity
{

    protected $position = null;
    protected $nextBinlogName;

    /**
     * @inheritDoc
     */
    public function getHumanReadableDescription()
    {
        return "Rotate Event, Next: " . $this->nextBinlogName . ($this->position !== null ? (" Position: " . $this->position) : "");
    }

    /**
     * @inheritDoc
     */
    public function parseBodyBuffer()
    {
        if (self::$currentFormatDescriptionEventEntity->binlogVersion > 1) {
            $this->position = $this->bodyBuffer->readNumberWithSomeBytesLE(0, 8);
            $this->nextBinlogName = $this->bodyBuffer->readStringEndedWithZero(8);
        }
    }
}