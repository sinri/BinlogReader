<?php


namespace sinri\BinlogReader;


class BRKit
{
    public static function breakDownVersionString($versionString)
    {
        $parts = explode('-', $versionString);
        return explode('.', $parts[0]);
    }

    public static function isAsOfVersion($versionString, $targetString)
    {
        $v = self::breakDownVersionString($versionString);
        $t = self::breakDownVersionString($targetString);
        for ($i = 0; $i < min(count($v), count($t)); $i++) {
            if ($v[$i] < $t[$i]) return false;
        }
        return true;
    }

    /**
     * @param int $x
     * @param int $bytes
     * @return string
     */
    public static function binOneNumber($x, $bytes = 1)
    {
        return '0b' . str_pad(decbin($x), 8 * $bytes, '0', STR_PAD_LEFT);
    }

    /**
     * @param BRByteBuffer|int[] $array
     * @return string
     * @deprecated
     */
    public static function binInlineNumbers($array)
    {
        if (is_a($array, BRByteBuffer::class)) {
            $numbers = $array->getBytesAsArray();
        } else {
            $numbers = $array;
        }
        $s = "";
        foreach ($numbers as $x) {
            $s .= self::binOneNumber($x, 1) . ' ';
        }
        return $s;
    }

    /**
     * @param int $x
     * @param int $bytes
     * @return string
     */
    public static function hexOneNumber(int $x, $bytes = 1)
    {
        //ArkHelper::quickNotEmptyAssert("must be int",is_int($x));
        return '0x' . str_pad(dechex($x), 2 * $bytes, '0', STR_PAD_LEFT);
    }

    /**
     * @param BRByteBuffer|int[] $array
     * @return string
     * @deprecated
     */
    public static function hexInlineNumbers($array)
    {
        if (is_a($array, BRByteBuffer::class)) {
            $numbers = $array->getBytesAsArray();
        } else {
            $numbers = $array;
        }
        $s = "";
        for ($i = 0; $i < count($numbers); $i++) {
            $s .= self::hexOneNumber($numbers[$i], 1) . ' ';
        }
        return $s;
    }

    /**
     * @param BRByteBuffer|int[] $array
     * @param int $rowNoBytes
     * @return string
     * @deprecated
     */
    public static function hexMatrixNumbers($array, $rowNoBytes = 3)
    {
        if (is_a($array, BRByteBuffer::class)) {
            $numbers = $array->getBytesAsArray();
        } else {
            $numbers = $array;
        }
        $s = "";
        for ($i = 0; $i < count($numbers); $i++) {
            if ($i > 0 && $i % 10 == 0) $s .= PHP_EOL;
            if ($i % 10 == 0) $s .= self::hexOneNumber($i, $rowNoBytes) . "\t|\t";
            $s .= self::hexOneNumber($numbers[$i], 1) . ' ';
        }
        return $s;
    }

    /**
     * @param string $string
     * @return string
     */
    public static function hexString($string)
    {
        $s = "";
        //var_dump($string);
        for ($i = 0; $i < strlen($string); $i++) {
            if ($i > 0) $s .= ' ';
            $s .= self::hexOneNumber(ord($string[$i]), 1);
        }
        return $s;
    }

    /**
     * @param int $timestamp
     * @return string
     */
    public static function refinedTime($timestamp)
    {
        return $timestamp . ' (' . date('Y-m-d H:i:s', $timestamp) . ')';
    }

    /**
     * @param BRByteBuffer|int[] $array [ BYTE-FIRST ... BYTE-LAST ] => [ BIT-LAST ... BIT-FIRST ]
     * @param int $length
     * @param callable $callback function($bit,$order) -> The [$order]th bit value is [$bit]
     * @deprecated
     */
    public static function checkBitmap($array, $length, callable $callback)
    {
        if (is_a($array, BRByteBuffer::class)) {
            $bitmapBuffer = $array->getBytesAsArray();
        } else {
            $bitmapBuffer = $array;
        }
        $order = 0;
        for ($bufferIndex = count($bitmapBuffer) - 1; $bufferIndex >= 0; $bufferIndex--) {
            $buffer = $bitmapBuffer[$bufferIndex];
            for ($i = 0; $i < 8; $i++) {
                $bit = $buffer % 2;

                call_user_func_array($callback, [$bit, $order]);

                $buffer = $buffer >> 1;
                $order++;
                if ($order >= $length) return;
            }
        }
    }
}