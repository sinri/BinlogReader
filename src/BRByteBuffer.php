<?php


namespace sinri\BinlogReader;


use Exception;
use sinri\ark\core\ArkHelper;
use sinri\BinlogReader\entity\BaseEventEntity;
use sinri\BinlogReader\entity\MySqlType\BlobType;
use sinri\BinlogReader\entity\MySqlType\DateTimeType;
use sinri\BinlogReader\entity\MySqlType\DateType;
use sinri\BinlogReader\entity\MySqlType\DecimalType;
use sinri\BinlogReader\entity\MySqlType\DoubleType;
use sinri\BinlogReader\entity\MySqlType\FloatType;
use sinri\BinlogReader\entity\MySqlType\Int24Type;
use sinri\BinlogReader\entity\MySqlType\LongIntType;
use sinri\BinlogReader\entity\MySqlType\LongLongIntType;
use sinri\BinlogReader\entity\MySqlType\ShortIntType;
use sinri\BinlogReader\entity\MySqlType\StringType;
use sinri\BinlogReader\entity\MySqlType\TimestampType;
use sinri\BinlogReader\entity\MySqlType\TimeType;
use sinri\BinlogReader\entity\MySqlType\TinyIntType;
use sinri\BinlogReader\entity\MySqlType\VarcharType;
use sinri\BinlogReader\entity\MySqlType\YearType;
use sinri\BinlogReader\entity\TableColumnTypeProtocol;

class BRByteBuffer
{
    /**
     * @var int[] array of bytes
     */
    public $bytes;

    public function __construct($bytes = null)
    {
        $this->bytes = [];
        if (is_array($bytes)) {
            $this->bytes = $bytes;
        }
    }

    public function getBytesAsArray()
    {
        return $this->bytes;
    }

    public function getSize()
    {
        return count($this->bytes);
    }

    public function showAsInlineBinary()
    {
        $s = "";
        foreach ($this->bytes as $x) {
            $s .= BRKit::binOneNumber($x, 1) . ' ';
        }
        return $s;
    }

    public function showAsInlineHex()
    {
        $s = "";
        for ($i = 0; $i < count($this->bytes); $i++) {
            $s .= BRKit::hexOneNumber($this->bytes[$i], 1) . ' ';
        }
        return $s;
    }

    public function showAsHexMatrix($rowNoBytes = 3)
    {
        $s = "";
        for ($i = 0; $i < count($this->bytes); $i++) {
            if ($i > 0 && $i % 10 == 0) $s .= PHP_EOL;
            if ($i % 10 == 0) $s .= BRKit::hexOneNumber($i, $rowNoBytes) . "\t|\t";
            $s .= BRKit::hexOneNumber($this->bytes[$i], 1) . ' ';
        }
        return $s;
    }

    /**
     * @param int $length
     * @param callable $callback function($bit,$order) -> The [$order]th bit value is [$bit]
     */
    public function checkBitmap($length, callable $callback)
    {
        $order = 0;
        for ($bufferIndex = count($this->bytes) - 1; $bufferIndex >= 0; $bufferIndex--) {
            $buffer = $this->bytes[$bufferIndex];
            for ($i = 0; $i < 8; $i++) {
                $bit = $buffer % 2;

                call_user_func_array($callback, [$bit, $order]);

                $buffer = $buffer >> 1;
                $order++;
                if ($order >= $length) return;
            }
        }
    }

    /**
     * @param int $index count since 0
     * @return int actually 0 or 1
     */
    public function checkBitmapItem($index)
    {
        $x = ($index + 8) >> 3;
        $byte = $this->bytes[$this->getSize() - $x];
        // index 1 -> index & 0x01 = 0b00000001
        // index 2 -> index & 0x02 = 0b00000010
        // index 3 -> index & 0x04 = 0b00000100
        // ...

        $y = ($index) % 8;
        $mask = 1 << $y;
        return $byte & $mask;
    }

    public function __toString()
    {
        return $this->showAsHexMatrix(3);
        //return BRKit::hexMatrixNumbers($this->bytes,3);
    }

    /**
     * @param int $offset
     * @param int $length
     * @throws Exception
     */
    protected function checkOffsetAndLength($offset, $length)
    {
        ArkHelper::quickNotEmptyAssert(
            "Array Boundary Beyond or Length Illegal",
            $offset >= 0,
            $offset < count($this->bytes),
            $length > 0,
            $offset + $length <= count($this->bytes)
        );
    }

    /**
     * @param resource $fileHandler
     * @param int $length
     * @return BRByteBuffer
     * @throws Exception
     */
    public static function pickFixedBuffer($fileHandler, $length)
    {
        ArkHelper::quickNotEmptyAssert("Buffer Length $length <= 0 ", $length > 0);
        ArkHelper::quickNotEmptyAssert("Buffer Length $length Should be Integer ", is_int($length));
        $buffer = new BRByteBuffer();
        for ($i = 0; $i < $length; $i++) {
            $char = fgetc($fileHandler);
            ArkHelper::quickNotEmptyAssert("Cannot Read Anymore To Buffer", $char !== false);
            $buffer->bytes[] = ord($char);
        }
        return $buffer;
    }

    /**
     * @param int $offset
     * @param int $length
     * @return int
     * @throws Exception
     */
    public function readNumberWithSomeBytesLE($offset, $length = null)
    {
        if ($length === null) {
            $length = $this->getSize() - $offset;
        }
        $this->checkOffsetAndLength($offset, $length);
        $num = 0;
        for ($i = $offset + $length - 1; $i >= $offset; $i--) {
            $num = ($num << 8) + $this->bytes[$i];
        }
        return $num;
    }

    /**
     * @param int $offset
     * @param int $length
     * @return int
     * @throws Exception
     */
    public function readNumberWithSomeBytesBE($offset, $length = null)
    {
        if ($length === null) {
            $length = $this->getSize() - $offset;
        }
        $this->checkOffsetAndLength($offset, $length);
        $num = 0;
        for ($i = $offset; $i < $offset + $length; $i++) {
            $num = ($num << 8) + $this->bytes[$i];
        }
        return $num;
    }

    /**
     * @param int $offset
     * @param int $length
     * @param bool $endWithZero
     * @return string
     * @throws Exception
     */
    public function readString($offset, $length, $endWithZero = true)
    {
        $this->checkOffsetAndLength($offset, $length);
        $string = "";
        for ($i = $offset; $i < $offset + $length; $i++) {
            $char = chr($this->bytes[$i]);
            if ($endWithZero && $char === "\0") {
                break;
            }
            $string .= $char;
        }
        return $string;
    }

    /**
     * @param int $offset
     * @param null|int $length output the real string length
     * @return string
     * @throws Exception
     */
    public function readStringEndedWithZero($offset, &$length = null)
    {
        $this->checkOffsetAndLength($offset, 1);
        $string = "";
        $length = 0;
        for ($i = $offset; $i < $this->getSize(); $i++) {
            $char = chr($this->bytes[$i]);
            $length += 1;
            if ($char === "\0") {
                break;
            }
            $string .= $char;
        }
        return $string;
    }

    /**
     * @param int $offset
     * @param null|int $length as output
     * @return int|null|false
     * @throws Exception
     */
    public function readLenencInt($offset, &$length = null)
    {
        $this->checkOffsetAndLength($offset, 1);
        $type = $this->readNumberWithSomeBytesLE($offset, 1);
        $length = 1;
        if ($type < 0xfb) {
            return $type;
        }
        if ($type == 0xfb) {
            // 0xfb代表NULL，就是mysql插入值往往会是空值，指的就是NULL
            return null;
        }
        if ($type == 0xfc) {
            $length += 2;
            return $this->readNumberWithSomeBytesLE($offset + 1, 2);
        }
        if ($type == 0xfd) {
            $length += 3;
            return $this->readNumberWithSomeBytesLE($offset + 1, 3);
        }
        if ($type == 0xfe) {
            $length += 8;
            return $this->readNumberWithSomeBytesLE($offset + 1, 8);
        }
        if ($type == 0xff) {
            // 0xff在mysql交互协议中一般代表某一个动作错误,如发送一个插入命令失败后会有这种回复出现。
            return false;
        }
        // ERROR now.
        return -1;
    }

    /**
     * @param int $offset
     * @param int $length
     * @return BRByteBuffer
     * @throws Exception
     */
    public function getSubByteBuffer($offset, $length = null)
    {
        if ($length === null) {
            $length = $this->getSize() - $offset;
        }
        $this->checkOffsetAndLength($offset, $length);
//        $subBytes=[];
//        for($i=$offset;$i<$offset+$length;$i++){
//            $subBytes[]=$this->bytes[$i];
//        }
        $subBytes = array_slice($this->bytes, $offset, $length);
        return new BRByteBuffer($subBytes);
    }

    /**
     * @param int $offset
     * @param int $length
     * @return BRByteBuffer
     * @throws Exception
     */
    public function getSubByteBufferAsCopy($offset, $length = null)
    {
        if ($length === null) {
            $length = $this->getSize() - $offset;
        }
        $this->checkOffsetAndLength($offset, $length);
        $subBytes = [];
        for ($i = $offset; $i < $offset + $length; $i++) {
            $subBytes[] = $this->bytes[$i];
        }
        return new BRByteBuffer($subBytes);
    }

    /**
     * @param int $type
     * @param BRByteBuffer $metaBuffer
     * @param int $offset
     * @param null|int $outputLength
     * @return mixed
     * @throws Exception
     */
    public function readValueByType($type, $metaBuffer, $offset, &$outputLength = null)
    {
        $mysqlServerVersion = BaseEventEntity::$currentFormatDescriptionEventEntity->serverVersion;
        $bufferSinceHere = $this->getSubByteBuffer($offset);
        switch ($type) {
            case TableColumnTypeProtocol::Protocol_MYSQL_TYPE_NEWDECIMAL:
            case TableColumnTypeProtocol::Protocol_MYSQL_TYPE_DECIMAL:
                // TODO implement the decimal type
                return (new DecimalType())->parseValue($metaBuffer, $bufferSinceHere, $outputLength);
//                return (new DecimalType())->readValueFromStream($reader,[
//                    ArkHelper::readTarget($metaBuffer,[0],10),//[1,65]
//                    ArkHelper::readTarget($metaBuffer,[1],0),//[0,30] and < M
//                ]);
            case TableColumnTypeProtocol::Protocol_MYSQL_TYPE_TINY:
                return (new TinyIntType())->parseValue(null, $bufferSinceHere, $outputLength);
            case TableColumnTypeProtocol::Protocol_MYSQL_TYPE_SHORT:
                return (new ShortIntType())->parseValue(null, $bufferSinceHere, $outputLength);
            case TableColumnTypeProtocol::Protocol_MYSQL_TYPE_INT24:
                return (new Int24Type())->parseValue(null, $bufferSinceHere, $outputLength);
            case TableColumnTypeProtocol::Protocol_MYSQL_TYPE_LONG:
                return (new LongIntType())->parseValue(null, $bufferSinceHere, $outputLength);
            case TableColumnTypeProtocol::Protocol_MYSQL_TYPE_LONGLONG:
                return (new LongLongIntType())->parseValue(null, $bufferSinceHere, $outputLength);

            case TableColumnTypeProtocol::Protocol_MYSQL_TYPE_FLOAT:
                return (new FloatType())->parseValue($metaBuffer, $bufferSinceHere, $outputLength);
            case TableColumnTypeProtocol::Protocol_MYSQL_TYPE_DOUBLE:
                return (new DoubleType())->parseValue($metaBuffer, $bufferSinceHere, $outputLength);

            case TableColumnTypeProtocol::Protocol_MYSQL_TYPE_TIMESTAMP2:
            case TableColumnTypeProtocol::Protocol_MYSQL_TYPE_TIMESTAMP:
                return (new TimestampType($mysqlServerVersion))->parseValue($metaBuffer, $bufferSinceHere, $outputLength);


            case TableColumnTypeProtocol::Protocol_MYSQL_TYPE_NEWDATE:
            case TableColumnTypeProtocol::Protocol_MYSQL_TYPE_DATE:
                return (new DateType())->parseValue($metaBuffer, $bufferSinceHere, $outputLength);
            case TableColumnTypeProtocol::Protocol_MYSQL_TYPE_TIME2:
            case TableColumnTypeProtocol::Protocol_MYSQL_TYPE_TIME:
                return (new TimeType($mysqlServerVersion))->parseValue($metaBuffer, $bufferSinceHere, $outputLength);
            case TableColumnTypeProtocol::Protocol_MYSQL_TYPE_DATETIME2:
            case TableColumnTypeProtocol::Protocol_MYSQL_TYPE_DATETIME:
                return (new DateTimeType($mysqlServerVersion))->parseValue($metaBuffer, $bufferSinceHere, $outputLength);
            case TableColumnTypeProtocol::Protocol_MYSQL_TYPE_YEAR:
                return (new YearType())->parseValue($metaBuffer, $bufferSinceHere, $outputLength);
            case TableColumnTypeProtocol::Protocol_MYSQL_TYPE_ENUM:
            case TableColumnTypeProtocol::Protocol_MYSQL_TYPE_SET:
            case TableColumnTypeProtocol::Protocol_MYSQL_TYPE_TINY_BLOB:
            case TableColumnTypeProtocol::Protocol_MYSQL_TYPE_MEDIUM_BLOB:
            case TableColumnTypeProtocol::Protocol_MYSQL_TYPE_LONG_BLOB:
            case TableColumnTypeProtocol::Protocol_MYSQL_TYPE_BLOB:
            case TableColumnTypeProtocol::Protocol_MYSQL_TYPE_GEOMETRY:
            case TableColumnTypeProtocol::Protocol_MYSQL_TYPE_BIT:
                return (new BlobType())->parseValue($metaBuffer, $bufferSinceHere, $outputLength);
            //return (new MixedBufferType())->readValueFromStream($reader,$metaBuffer);
            case TableColumnTypeProtocol::Protocol_MYSQL_TYPE_VARCHAR:
            case TableColumnTypeProtocol::Protocol_MYSQL_TYPE_VAR_STRING:
                BREnv::getLogger()->debug(__METHOD__, ['type' => $type, 'meta' => $metaBuffer]);
                return (new VarcharType())->parseValue($metaBuffer, $bufferSinceHere, $outputLength);
            case TableColumnTypeProtocol::Protocol_MYSQL_TYPE_STRING:
                return (new StringType())->parseValue($metaBuffer, $bufferSinceHere, $outputLength);
            case TableColumnTypeProtocol::Protocol_MYSQL_TYPE_NULL:
                // stored in the NULL-bitmap only
                return null;
            default:
                // unknown type here
                return false;
        }
    }
}