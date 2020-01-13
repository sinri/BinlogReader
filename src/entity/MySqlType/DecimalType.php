<?php


namespace sinri\BinlogReader\entity\MySqlType;


use Exception;
use sinri\ark\core\ArkHelper;

/**
 * Class DecimalType
 * @package sinri\BinlogReader\entity\MySqlType
 * @see GitHub Repo mysql-server 5.6 /strings/decimal.c
 */
class DecimalType extends BaseType
{
    const DIG_PER_DEC1=9;

    protected static $dig2bytes=[0,1,1,2,2,3,3,4,4,4];

    /**
     * @param $precision
     * @param $scale
     * @return float|int|mixed
     * @throws Exception
     */
    public function getBinSizeForPrecisionAndScale($precision,$scale){
        $size_of_dec1=4; // when decimal_digit_t defined as dec1 actually as int32 in <decimal.h>

        $intg=$precision-$scale;
        $intg0=$intg/self::DIG_PER_DEC1;
        $frac0=$scale/self::DIG_PER_DEC1;
        $intg0x=$intg-$intg0*self::DIG_PER_DEC1;
        $frac0x=$scale-$frac0*self::DIG_PER_DEC1;

        ArkHelper::quickNotEmptyAssert($scale>=0 && $precision>0 && $scale<$precision);

        return $intg0*$size_of_dec1+self::$dig2bytes[$intg0x]+$frac0*$size_of_dec1+self::$dig2bytes[$frac0x];
    }

    public function decimal2bin($decimal,$precision,$scale){
        // TODO
        // method:
        // 1234567890.1234  -> 1 234567890 . 1234
        // For $precision M=14, $scale D=4,
        // M  1: 1 -> 0x00000001 -> 0x01
        // M  9: 234567890 -> 0x0dfb38d2 -> 0x0dfb38d2
        // MD 4: 1234 -> 0x000004d2 -> 0x04d2
        // -> 0x01 0x0dfb38d2 . 0x04d2 totally 7 bytes
        // -> invert the first bit 0x81 0x0dfb38d2 . 0x04d2
        // above is positive (started with bit 1), if negative, revert all bits (started with bit 0)
        // -> 0x7e 0xf204c72d 0xfb2d
    }

    /**
     * @param int[] $bytes
     * @param int $m
     * @param int $d
     */
    public function bin2decimal($bytes,$m,$d){
        // 1. confirm positive or negative
        $isNegative=$bytes[0] < 0b10000000;

        // 2. turn back to original bytes
        $tempBytes=[];
        for($i=0;$i<count($bytes);$i++){
            $tempByte=$bytes[$i]*1;
            if($isNegative) {
                $tempByte = 0x11111111 ^ $tempByte;
            }

            if($i==0){
                $tempByte=0x10000000 ^ $tempByte;
            }

            $tempBytes[]=$tempByte;
        }

        // 3. fetch bytes from right
        $number=0;

        // TODO

        for($i=0;$i<count($tempBytes);$i++){
            if($i<$m-$d){
                // X.y
            }else{
                // x.Y
            }
        }
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function getValueSize($meta = null)
    {
        return $this->getBinSizeForPrecisionAndScale($meta[0], $meta[1]);
    }

    /**
     * @inheritDoc
     */
    function readValueFromStream($reader, $meta = null)
    {
        return $this->readBufferFromStream($reader, $meta);
    }
}