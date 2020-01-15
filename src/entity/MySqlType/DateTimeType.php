<?php


namespace sinri\BinlogReader\entity\MySqlType;


use sinri\BinlogReader\BREnv;
use sinri\BinlogReader\BRKit;

class DateTimeType extends DateType
{
    const VERSION_BEFORE_5_6_4="<5.6.4";
    const VERSION_AS_OF_5_6_4=">=5.6.4";

    protected $version;

    protected $fsp;

    protected $isNegative;

    protected $hour;
    protected $minute;
    protected $second;
    protected $microSecond;

    public function __construct($versionString)
    {
        $this->version=(BRKit::isAsOfVersion($versionString,'5.6.4')?self::VERSION_AS_OF_5_6_4:self::VERSION_BEFORE_5_6_4);
        $this->isNegative=false;
    }

    public function parseValue($metaBuffer, $buffer, &$outputLength = null)
    {
        if ($this->version == self::VERSION_BEFORE_5_6_4) {
            parent::parseValue($metaBuffer, $buffer, $outputLength);
        } else {
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
        }

        BREnv::getLogger()->debug("BUFFER: " . $this->contentByteBuffer->showAsInlineBinary());

        return ($this->isNegative ? '-' : '') . $this->makeDateString() . ' ' . $this->makeTimeString();
    }

    protected function makeDateString()
    {
        $this->isNegative = (($this->readByteInBuffer(0) & 0b10000000) >> 7 == 0); // 1
        $yearMonth17bits = ((($this->readByteInBuffer(0) & 0b01111111)) << 10)
            + ($this->readByteInBuffer(1) << 2)
            + (($this->readByteInBuffer(2) & 0b11000000) >> 6);// year*13+month, 7+8+2
        $this->year = floor($yearMonth17bits / 13);
        $this->month = $yearMonth17bits % 13;
        $this->day = ($this->readByteInBuffer(2) & 0b00111110) >> 1;// 5

        return ($this->isNegative ? 'BC' : 'AD') . $this->year . '-' . ($this->month < 10 ? '0' : '') . $this->month . '-' . ($this->day < 10 ? '0' : '') . $this->day;
    }

    public function makeTimeString()
    {
        if ($this->version == self::VERSION_BEFORE_5_6_4) {
            $this->hour = $this->readByteInBuffer(4);
            $this->minute = $this->readByteInBuffer(5);
            $this->second = $this->readByteInBuffer(6);

            $this->microSecond = ($this->readByteInBuffer(10) << 24)
                + ($this->readByteInBuffer(9) << 16)
                + ($this->readByteInBuffer(8) << 8)
                + ($this->readByteInBuffer(7));
        }else{
            $this->hour=(($this->readByteInBuffer(2) & 0b00000001)<<8)
                +($this->readByteInBuffer(3) & 0b11110000)>>4;//1+4
            $this->minute=(($this->readByteInBuffer(3) & 0b00001111)<<2)
                +(($this->readByteInBuffer(4) & 0b11000000)>>6);//4+2
            $this->second=($this->readByteInBuffer(4) & 0b00111111);//6

            if($this->fsp>=5){
                $this->microSecond=($this->readByteInBuffer(5)<<16)
                    +($this->readByteInBuffer(6)<<8)
                    +$this->readByteInBuffer(7);
            }elseif($this->fsp>=3){
                $this->microSecond=($this->readByteInBuffer(5)<<8)
                    +$this->readByteInBuffer(6);
            }elseif($this->fsp>0){
                $this->microSecond=$this->readByteInBuffer(5);
            }else{
                $this->microSecond=0;
            }
        }
        return ($this->hour < 10 ? '0' : '') . $this->hour . ':'
            . ($this->minute < 10 ? '0' : '') . $this->minute . ':'
            . ($this->second < 10 ? '0' : '') . $this->second
            . ($this->microSecond > 0 ? (' ' . str_pad($this->microSecond, 3, '0', STR_PAD_LEFT)) : '');
    }
}