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
        parent::parseValue($metaBuffer, $buffer, $outputLength);

        $this->year = $this->contentByteBuffer->readNumberWithSomeBytesBE(0, 2);
        $this->month = $this->contentByteBuffer->readNumberWithSomeBytesBE(2, 1);
        $this->day = $this->contentByteBuffer->readNumberWithSomeBytesBE(3, 1);

        return $this->makeDateString();
    }

    protected function makeDateString(){
        return $this->year.'-'.($this->month<10?'0':'').$this->month.'-'.($this->day<10?'0':'').$this->day;
    }
}