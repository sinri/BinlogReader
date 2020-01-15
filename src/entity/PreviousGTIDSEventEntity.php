<?php


namespace sinri\BinlogReader\entity;


class PreviousGTIDSEventEntity extends BaseEventEntity
{

    /**
     * @inheritDoc
     */
    public function getHumanReadableDescription()
    {
        return 'Mixed Body: ' . PHP_EOL . $this->bodyBuffer->showAsHexMatrix();
    }

    /**
     * @inheritDoc
     */
    public function parseBodyBuffer()
    {
        // know nothing about it, let the body data as is
    }
}