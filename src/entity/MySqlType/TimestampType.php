<?php


namespace sinri\BinlogReader\entity\MySqlType;


use sinri\BinlogReader\BRKit;

class TimestampType extends BaseType
{
    // @see https://blog.csdn.net/ppvqq/article/details/47424163
    // TIMESTAMP
    // Storage before MySQL 5.6.4
    // 4 bytes, little endian
    // Storage as of MySQL 5.6.4
    // 4 bytes + fractional-seconds storage, big endian
    // However @see https://dev.mysql.com/doc/internals/en/binary-protocol-value.html
    // Timestamp is a package of bytes

    const VERSION_BEFORE_5_6_4 = "<5.6.4";
    const VERSION_AS_OF_5_6_4 = ">=5.6.4";

    protected $version;

    protected $fsp;

    public function __construct($versionString)
    {
        $this->version = (BRKit::isAsOfVersion($versionString, '5.6.4') ? self::VERSION_AS_OF_5_6_4 : self::VERSION_BEFORE_5_6_4);
    }

    /**
     * @inheritDoc
     */
    public function parseValue($metaBuffer, $buffer, &$outputLength = null)
    {
        if ($this->version === self::VERSION_BEFORE_5_6_4) {
            $outputLength = 4;
            return $buffer->readNumberWithSomeBytesLE(0, 4);
        } else {
            $this->fsp = $metaBuffer->readNumberWithSomeBytesLE(0, 1);
            $outputLength = 5;
            if ($this->fsp >= 5) {
                $outputLength += 3;
            } elseif ($this->fsp >= 3) {
                $outputLength += 2;
            } elseif ($this->fsp > 0) {
                $outputLength += 1;
            }

            $x = $buffer->readNumberWithSomeBytesBE(0, 5);
            $y = $buffer->readNumberWithSomeBytesBE(5, $outputLength - 5);
            return $x . '.' . $y;
        }
    }
}