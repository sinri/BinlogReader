<?php


namespace sinri\BinlogReader\entity\MySqlType;


class TimeType extends MixedBufferType
{
//    const VERSION_BEFORE_5_6_4="<5.6.4";
//    const VERSION_AS_OF_5_6_4=">=5.6.4";
//
//    protected $version;

    protected $isNegative;
    //protected $days;
    protected $hours;
    protected $minutes;
    protected $seconds;
    protected $microSeconds;

    public function parseValue($metaBuffer, $buffer, &$outputLength = null)
    {
        parent::parseValue($metaBuffer, $buffer, $outputLength);

        $time = $this->contentByteBuffer->readNumberWithSomeBytesLE(0, 3);
        $this->hours = (int)floor($time / 10000);
        $this->minutes = (int)floor(($time % 10000) / 100);
        $this->seconds = (int)($time % 100);

//            if ($this->readByteInBuffer(0) > 0) {
//                $this->isNegative = ($this->readByteInBuffer(1, 0) == 1);
//                $this->days = ($this->readByteInBuffer(5) << 24)
//                    + ($this->readByteInBuffer(4) << 16)
//                    ($this->readByteInBuffer(3) << 8)
//                    ($this->readByteInBuffer(2) << 0);
//                $this->hours = $this->readByteInBuffer(6);
//                $this->minutes = $this->readByteInBuffer(7);
//                $this->seconds = $this->readByteInBuffer(8);
//                if ($this->readByteInBuffer(0) > 8) {
//                    $this->microSeconds = ($this->readByteInBuffer(12) << 24)
//                        + ($this->readByteInBuffer(11) << 16)
//                        + ($this->readByteInBuffer(10) << 8)
//                        + ($this->readByteInBuffer(9) << 0);
//                }
//            }

        return $this->makeTimesString();
    }

    protected function makeTimesString()
    {
        return ($this->isNegative ? '-' : '')
            //. $this->days . 'D'
            . $this->hours . 'H'
            . $this->minutes . 'M'
            . $this->seconds . 'S'
            . $this->microSeconds;
    }
}