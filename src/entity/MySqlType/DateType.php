<?php


namespace sinri\BinlogReader\entity\MySqlType;


class DateType extends MixedBufferType
{
    protected $year;
    protected $month;
    protected $day;

    /**
     * @inheritDoc
     */
    public function parseValue($metaBuffer, $buffer, &$outputLength = null)
    {
        //parent::parseValue($metaBuffer, $buffer, $outputLength);
        $this->lengthByteCount = 0;
        $this->valueByteCount = 3;
        $outputLength = $this->lengthByteCount + $this->valueByteCount;
        $this->contentByteBuffer = $buffer->getSubByteBuffer(0, $outputLength);

        // 0x2a (*),0xd8 (<D8>),0x0e -> '1900:01:10'
        // 0b11101101100000101010
        $x = $this->contentByteBuffer->readNumberWithSomeBytesLE(0, 3);
        $this->year = (int)floor($x / (16 * 32));
        $this->month = (int)floor(($x - $this->year * 16 * 32) / 32);
        $this->day = $x - $this->year * 16 * 32 - $this->month * 32;

        //$this->year = $this->contentByteBuffer->readNumberWithSomeBytesBE(0, 2);
        //$this->month = $this->contentByteBuffer->readNumberWithSomeBytesBE(2, 1);
        //$this->day = $this->contentByteBuffer->readNumberWithSomeBytesBE(3, 1);

        return $this->makeDateString();
    }

    protected function makeDateString(){
        return $this->year.'-'.($this->month<10?'0':'').$this->month.'-'.($this->day<10?'0':'').$this->day;
    }
}