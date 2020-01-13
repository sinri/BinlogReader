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
    function readValueFromStream($reader, $meta = null)
    {
        if ($this->size === null) {
            $this->read($reader);
        }

        return $this->makeDateString();
    }

    protected function makeDateString(){
        $this->year=($this->readByteInBuffer(0)<<8)+$this->readByteInBuffer(1);
        $this->month=$this->readByteInBuffer(2);
        $this->day=$this->readByteInBuffer(3);

        return $this->year.'-'.($this->month<10?'0':'').$this->month.'-'.($this->day<10?'0':'').$this->day;
    }
}