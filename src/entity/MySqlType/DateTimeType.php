<?php


namespace sinri\BinlogReader\entity\MySqlType;


class DateTimeType extends MixedBufferType
{
    protected $isNegative;

    protected $year;
    protected $month;
    protected $day;

    protected $hour;
    protected $minute;
    protected $second;
    protected $microSecond;

    protected function makeDateString()
    {
        return ($this->isNegative ? 'BC' : 'AD') . $this->year . '-' . ($this->month < 10 ? '0' : '') . $this->month . '-' . ($this->day < 10 ? '0' : '') . $this->day;
    }

    protected function makeTimeString()
    {
        return ($this->hour < 10 ? '0' : '') . $this->hour . ':'
            . ($this->minute < 10 ? '0' : '') . $this->minute . ':'
            . ($this->second < 10 ? '0' : '') . $this->second
            . ($this->microSecond > 0 ? (' ' . str_pad($this->microSecond, 3, '0', STR_PAD_LEFT)) : '');
    }

    public function parseValue($metaBuffer, $buffer, &$outputLength = null)
    {
        $this->lengthByteCount = 0;
        $this->valueByteCount = 8;
        $outputLength = $this->lengthByteCount + $this->valueByteCount;
        $this->contentByteBuffer = $buffer->getSubByteBuffer(0, $outputLength);

        $value = $this->contentByteBuffer->readNumberWithSomeBytesBE(0, 8);
        $this->isNegative = $value < 0;
        if ($this->isNegative) {
            $value = -$value;
        }

        $date = (int)floor($value / 1000000);
        $time = (int)($value % 1000000);

        $this->year = (int)floor($date / 10000);
        $this->month = (int)floor(($date % 10000) / 100);
        $this->day = (int)($date % 100);

        $this->hour = (int)floor($time / 10000);
        $this->minute = (int)floor(($time % 10000) / 100);
        $this->second = (int)($time % 100);

        return $this->makeDateString() . ' ' . $this->makeTimeString();
    }
}