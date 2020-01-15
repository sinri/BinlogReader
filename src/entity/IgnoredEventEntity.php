<?php


namespace sinri\BinlogReader\entity;


class IgnoredEventEntity extends BaseEventEntity
{

    /**
     * @inheritDoc
     */
    public function getHumanReadableDescription()
    {
        return $this->bodyBuffer->showAsHexMatrix();
    }

    /**
     * @inheritDoc
     */
    public function parseBodyBuffer()
    {
        // ignored
    }
}