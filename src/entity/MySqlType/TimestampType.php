<?php


namespace sinri\BinlogReader\entity\MySqlType;


class TimestampType extends LongIntType
{
    // @see https://blog.csdn.net/ppvqq/article/details/47424163
    // TIMESTAMP
    // Storage before MySQL 5.6.4
    // 4 bytes, little endian
    // TIMESTAMP2
    // Storage as of MySQL 5.6.4
    // 4 bytes + fractional-seconds storage, big endian
    // However @see https://dev.mysql.com/doc/internals/en/binary-protocol-value.html
    // Timestamp is a package of bytes
}