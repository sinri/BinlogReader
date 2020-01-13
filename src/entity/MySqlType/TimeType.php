<?php


namespace sinri\BinlogReader\entity\MySqlType;


use sinri\BinlogReader\BRKit;

class TimeType extends MixedBufferType
{
    const VERSION_BEFORE_5_6_4="<5.6.4";
    const VERSION_AS_OF_5_6_4=">=5.6.4";

    protected $version;

    protected $isNegative;
    protected $days;
    protected $hours;
    protected $minutes;
    protected $seconds;
    protected $microSeconds;

    protected $fsp;

    public function __construct($versionString)
    {
        $this->version=(BRKit::isAsOfVersion($versionString,'5.6.4')?self::VERSION_AS_OF_5_6_4:self::VERSION_BEFORE_5_6_4);

        $this->isNegative=false;
        $this->days=0;
        $this->hours=0;
        $this->minutes=0;
        $this->seconds=0;
        $this->microSeconds=0;
    }

    protected function read($reader)
    {
        if($this->version==self::VERSION_BEFORE_5_6_4) {
            parent::read($reader);
        }else{
            $this->size=3;
            if($this->fsp>=5){
                $this->size+=3;
            }elseif($this->fsp>=3){
                $this->size+=2;
            }elseif($this->fsp>0){
                $this->size+=1;
            }

            $this->buffer=$reader->readByteBuffer($this->size);
        }
    }

    /**
     * @inheritDoc
     */
    function readValueFromStream($reader, $meta = [])
    {
        if ($this->size === null) {
            $this->read($reader);
        }
        if($this->version==self::VERSION_AS_OF_5_6_4){
            $this->fsp=$meta;
        }
        if ($this->version == self::VERSION_BEFORE_5_6_4) {
            if ($this->readByteInBuffer(0) > 0) {
                $this->isNegative = ($this->readByteInBuffer(1, 0) == 1);
                $this->days = ($this->readByteInBuffer(5) << 24)
                    + ($this->readByteInBuffer(4) << 16)
                    ($this->readByteInBuffer(3) << 8)
                    ($this->readByteInBuffer(2) << 0);
                $this->hours = $this->readByteInBuffer(6);
                $this->minutes = $this->readByteInBuffer(7);
                $this->seconds = $this->readByteInBuffer(8);
                if ($this->readByteInBuffer(0) > 8) {
                    $this->microSeconds = ($this->readByteInBuffer(12) << 24)
                        + ($this->readByteInBuffer(11) << 16)
                        + ($this->readByteInBuffer(10) << 8)
                        + ($this->readByteInBuffer(9) << 0);
                }
            }
        } else {
            $this->isNegative=($this->readByteInBuffer(0)==0);// 1 + (1)
            $this->hours=(($this->readByteInBuffer(0) & 0b00111111)<<4)
                +(($this->readByteInBuffer(1) & 0b11110000)>>4);//6+4
            $this->minutes=(($this->readByteInBuffer(1) & 0b00001111)<<2)
                +(($this->readByteInBuffer(2) & 0b11000000)>>6);
            $this->seconds=$this->readByteInBuffer(2) & 0b00111111;

            if($this->fsp>=5){
                $this->microSeconds=($this->readByteInBuffer(3)<<16)
                    +($this->readByteInBuffer(4)<<8)
                    +$this->readByteInBuffer(5);
            }elseif($this->fsp>=3){
                $this->microSeconds=($this->readByteInBuffer(3)<<8)
                    +$this->readByteInBuffer(4);
            }elseif($this->fsp>0){
                $this->microSeconds=$this->readByteInBuffer(3);
            }else{
                $this->microSeconds=0;
            }
        }
        return ($this->isNegative ? '-' : '')
            . $this->days . 'D'
            . $this->hours . 'H'
            . $this->minutes . 'M'
            . $this->seconds . 'S'
            . $this->microSeconds;
    }
}