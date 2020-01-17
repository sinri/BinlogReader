<?php


namespace sinri\BinlogReader\entity\MySqlType;


use Exception;
use sinri\ark\core\ArkHelper;
use sinri\BinlogReader\BRByteBuffer;
use sinri\BinlogReader\BREnv;

class NewDecimalType extends BaseType
{
    const DIG_PER_DEC1 = 9;
    protected static $dig2bytes = [0, 1, 1, 2, 2, 3, 3, 4, 4, 4];

    /**
     * @param $precision
     * @param $scale
     * @return float|int|mixed
     * @throws Exception
     */
    public function getBinSizeForPrecisionAndScale($precision, $scale)
    {
        $size_of_dec1 = 4; // when decimal_digit_t defined as dec1 actually as int32 in <decimal.h>

        $intg = $precision - $scale;
        $intg0 = (int)floor($intg / self::DIG_PER_DEC1);
        $frac0 = (int)floor($scale / self::DIG_PER_DEC1);
        $intg0x = $intg - $intg0 * self::DIG_PER_DEC1;
        $frac0x = $scale - $frac0 * self::DIG_PER_DEC1;

        ArkHelper::quickNotEmptyAssert($scale >= 0 && $precision > 0 && $scale < $precision);

        return $intg0 * $size_of_dec1 + self::$dig2bytes[$intg0x] + $frac0 * $size_of_dec1 + self::$dig2bytes[$frac0x];
    }

    /**
     * @inheritDoc
     */
    public function parseValue($metaBuffer, $buffer, &$outputLength = null)
    {
        $precision = $metaBuffer->readNumberWithSomeBytesLE(0, 1);
        $scale = $metaBuffer->readNumberWithSomeBytesLE(1, 1);
        $outputLength = $this->getBinSizeForPrecisionAndScale($precision, $scale);

        $integral = ($precision - $scale);
        $uncomp_integral = (int)floor($integral / self::DIG_PER_DEC1);
        $uncomp_fractional = (int)floor($scale / self::DIG_PER_DEC1);
        $comp_integral = $integral - ($uncomp_integral * self::DIG_PER_DEC1);
        $comp_fractional = $scale - ($uncomp_fractional * self::DIG_PER_DEC1);

        $offset = 0;

        BREnv::getLogger()->alert(__METHOD__ . '@' . __LINE__ . ' ' . $buffer->getSubByteBuffer(0, $outputLength)->showAsInlineHex(), ['precision' => $precision, 'scale' => $scale, 'bytes' => $outputLength]);

        // Support negative
        // The sign is encoded in the high bit of the the byte
        // But this bit can also be used in the value
//        $value = $buffer->readNumberWithSomeBytesLE($offset,1);// offset not move right now
//        if ($value & 0x80 != 0) {
//            $res = "";
//            //$mask = 0;
//        }
//        else {
//            //$mask = -1;
//            $res = "-";
//            $buffer=$this->reverseBitsInByteBuffer($buffer->getSubByteBuffer(0,$outputLength));
//        }

        $buffer = $this->pickRealBuffer($buffer->getSubByteBuffer(0, $outputLength), $isNegative);

        BREnv::getLogger()->critical(__METHOD__ . '@' . __LINE__ . ' ABSed ' . $buffer->getSubByteBuffer(0, $outputLength)->showAsInlineHex(), ['isNegative' => $isNegative]);

        $integerString = "";

        $size = self::$dig2bytes[$comp_integral];
        BREnv::getLogger()->alert(__METHOD__ . '@' . __LINE__ . ' size=' . $size);
        if ($size > 0) {
            $value = $buffer->readNumberWithSomeBytesBE($offset, $size);// ^ $mask;
            $offset += $size;

            BREnv::getLogger()->critical("NEW DECIMAL INTEGER PART 1: " . dechex($value));

            $integerString .= $value;
        }
        BREnv::getLogger()->alert(__METHOD__ . '@' . __LINE__ . ' uncomp_integral=' . $uncomp_integral);
        for ($i = 0; $i < $uncomp_integral; $i++) {
            $value = $buffer->readNumberWithSomeBytesBE($offset, 4);// ^ $mask;
            $offset += 4;

            BREnv::getLogger()->critical("NEW DECIMAL INTEGER PART 2: " . dechex($value));

            $integerString .= $value;
        }

        $fractionalString = "";

        BREnv::getLogger()->alert(__METHOD__ . '@' . __LINE__ . ' uncomp_fractional=' . $uncomp_fractional);
        for ($i = 0; $i < $uncomp_fractional; $i++) {
            $value = $buffer->readNumberWithSomeBytesBE($offset, 4);// ^ $mask;
            $offset += 4;

            BREnv::getLogger()->critical("NEW DECIMAL FRACTIONAL PART 1: " . dechex($value));

            $fractionalString .= $value;
        }


        $size = self::$dig2bytes[$comp_fractional];
        BREnv::getLogger()->alert(__METHOD__ . '@' . __LINE__ . ' size=' . $size);
        if ($size > 0) {
            $value = $buffer->readNumberWithSomeBytesBE($offset, $size);// ^ $mask;
            $offset += $size;

            BREnv::getLogger()->critical("NEW DECIMAL FRACTIONAL PART 2: " . dechex($value));

            $fractionalString .= $value;
        }

        $integerString = ltrim($integerString, "0");
        //$fractionalString=rtrim($fractionalString,"0");
        $fractionalString = str_pad($fractionalString, $scale, '0', STR_PAD_RIGHT);

        return ($isNegative ? '-' : '') . $integerString . ($fractionalString !== '' ? ("." . $fractionalString) : "");
    }

    /**
     * @param BRByteBuffer $buffer
     * @param bool $isNegative
     * @return BRByteBuffer
     * @throws Exception
     */
    private function pickRealBuffer($buffer, &$isNegative)
    {
        $firstByte = $buffer->readNumberWithSomeBytesLE(0, 1);
        $isNegative = ($firstByte & 0x80) === 0;

        $array = [];
        for ($i = 0; $i < $buffer->getSize(); $i++) {
            $t = $buffer->readNumberWithSomeBytesLE($i, 1);
            if ($isNegative) {
                $t = $t ^ 0xff;
            }
            if ($i == 0) {
                $t = $t ^ 0b10000000;
            }
            $array[] = $t;
        }

        return new BRByteBuffer($array);
    }
}