<?php


namespace sinri\BinlogReader\entity\MySqlType;


class DateTime2Type extends DateTimeType
{
    protected $fsp;

    /**
     * @inheritDoc
     */
    public function parseValue($metaBuffer, $buffer, &$outputLength = null)
    {
        $this->fsp = $metaBuffer->readNumberWithSomeBytesLE(0, 1);
        $this->lengthByteCount = 0;
        $this->valueByteCount = 5;
        if ($this->fsp >= 5) {
            $this->valueByteCount += 3;
        } elseif ($this->fsp >= 3) {
            $this->valueByteCount += 2;
        } elseif ($this->fsp > 0) {
            $this->valueByteCount += 1;
        }

        $this->contentByteBuffer = $buffer->getSubByteBuffer($this->lengthByteCount, $this->valueByteCount);
        $outputLength = $this->valueByteCount + $this->lengthByteCount;

        //BREnv::getLogger()->debug("BUFFER: " . $this->contentByteBuffer->showAsInlineBinary());

        $this->isNegative = (($this->readByteInBuffer(0) & 0b10000000) >> 7 == 0); // 1
        $yearMonth17bits = ((($this->readByteInBuffer(0) & 0b01111111)) << 10)
            + ($this->readByteInBuffer(1) << 2)
            + (($this->readByteInBuffer(2) & 0b11000000) >> 6);// year*13+month, 7+8+2
        $this->year = floor($yearMonth17bits / 13);
        $this->month = $yearMonth17bits % 13;
        $this->day = ($this->readByteInBuffer(2) & 0b00111110) >> 1;// 5

        $this->hour = (($this->readByteInBuffer(2) & 0b00000001) << 8)
            + ($this->readByteInBuffer(3) & 0b11110000) >> 4;//1+4
        $this->minute = (($this->readByteInBuffer(3) & 0b00001111) << 2)
            + (($this->readByteInBuffer(4) & 0b11000000) >> 6);//4+2
        $this->second = ($this->readByteInBuffer(4) & 0b00111111);//6

        if ($this->fsp >= 5) {
            $this->microSecond = ($this->readByteInBuffer(5) << 16)
                + ($this->readByteInBuffer(6) << 8)
                + $this->readByteInBuffer(7);
        } elseif ($this->fsp >= 3) {
            $this->microSecond = ($this->readByteInBuffer(5) << 8)
                + $this->readByteInBuffer(6);
        } elseif ($this->fsp > 0) {
            $this->microSecond = $this->readByteInBuffer(5);
        } else {
            $this->microSecond = 0;
        }

        return $this->makeDateString() . ' ' . $this->makeTimeString();
    }
}