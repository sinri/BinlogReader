<?php


namespace sinri\BinlogReader\entity\MySqlType;


class Time2Type extends TimeType
{

    protected $fsp;

    public function parseValue($metaBuffer, $buffer, &$outputLength = null)
    {
        $this->fsp = $metaBuffer->readNumberWithSomeBytesLE(0, 1);

        $this->lengthByteCount = 0;
        $this->valueByteCount = 3;
        if ($this->fsp >= 5) {
            $this->valueByteCount += 3;
        } elseif ($this->fsp >= 3) {
            $this->valueByteCount += 2;
        } elseif ($this->fsp > 0) {
            $this->valueByteCount += 1;
        }

        $this->contentByteBuffer = $buffer->getSubByteBuffer($this->lengthByteCount, $this->valueByteCount);
        $outputLength = $this->valueByteCount + $this->lengthByteCount;

        $this->isNegative = ($this->readByteInBuffer(0) == 0);// 1 + (1)
        $this->hours = (($this->readByteInBuffer(0) & 0b00111111) << 4)
            + (($this->readByteInBuffer(1) & 0b11110000) >> 4);//6+4
        $this->minutes = (($this->readByteInBuffer(1) & 0b00001111) << 2)
            + (($this->readByteInBuffer(2) & 0b11000000) >> 6);
        $this->seconds = $this->readByteInBuffer(2) & 0b00111111;

        if ($this->fsp >= 5) {
            $this->microSeconds = ($this->readByteInBuffer(3) << 16)
                + ($this->readByteInBuffer(4) << 8)
                + $this->readByteInBuffer(5);
        } elseif ($this->fsp >= 3) {
            $this->microSeconds = ($this->readByteInBuffer(3) << 8)
                + $this->readByteInBuffer(4);
        } elseif ($this->fsp > 0) {
            $this->microSeconds = $this->readByteInBuffer(3);
        } else {
            $this->microSeconds = 0;
        }

        return $this->makeTimesString();
    }
}