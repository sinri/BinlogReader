<?php


namespace sinri\BinlogReader\entity;

/**
 * Class TableColumnTypeProtocol
 * @package sinri\BinlogReader\entity
 * @see https://dev.mysql.com/doc/internals/en/com-query-response.html#packet-Protocol::ColumnType
 * @see https://dev.mysql.com/doc/internals/en/binary-protocol-value.html Note this is before 5.6.4
 * @see https://blog.csdn.net/ppvqq/article/details/47424163 How Date and Time stored before and after 5.6.4
 * @see https://dev.mysql.com/doc/refman/5.6/en/storage-requirements.html For Special String Types VC,Enum,etc.
 * @see https://www.cnblogs.com/billyxp/p/3548540.html as well above
 */
class TableColumnTypeProtocol
{
    const Protocol_MYSQL_TYPE_DECIMAL = 0x00;//	Implemented by ProtocolBinary_MYSQL_TYPE_DECIMAL
    const Protocol_MYSQL_TYPE_TINY = 0x01;//	Implemented by ProtocolBinary_MYSQL_TYPE_TINY
    const Protocol_MYSQL_TYPE_SHORT = 0x02;//	Implemented by ProtocolBinary_MYSQL_TYPE_SHORT
    const Protocol_MYSQL_TYPE_LONG = 0x03;//	Implemented by ProtocolBinary_MYSQL_TYPE_LONG
    const Protocol_MYSQL_TYPE_FLOAT = 0x04;//	Implemented by ProtocolBinary_MYSQL_TYPE_FLOAT
    const Protocol_MYSQL_TYPE_DOUBLE = 0x05;//	Implemented by ProtocolBinary_MYSQL_TYPE_DOUBLE
    const Protocol_MYSQL_TYPE_NULL = 0x06;//	Implemented by ProtocolBinary_MYSQL_TYPE_NULL
    const Protocol_MYSQL_TYPE_TIMESTAMP = 0x07;//	Implemented by ProtocolBinary_MYSQL_TYPE_TIMESTAMP
    const Protocol_MYSQL_TYPE_LONGLONG = 0x08;//	Implemented by ProtocolBinary_MYSQL_TYPE_LONGLONG
    const Protocol_MYSQL_TYPE_INT24 = 0x09;//	Implemented by ProtocolBinary_MYSQL_TYPE_INT24
    const Protocol_MYSQL_TYPE_DATE = 0x0a;//	Implemented by ProtocolBinary_MYSQL_TYPE_DATE
    const Protocol_MYSQL_TYPE_TIME = 0x0b;//	Implemented by ProtocolBinary_MYSQL_TYPE_TIME
    const Protocol_MYSQL_TYPE_DATETIME = 0x0c;//	Implemented by ProtocolBinary_MYSQL_TYPE_DATETIME
    const Protocol_MYSQL_TYPE_YEAR = 0x0d;//	Implemented by ProtocolBinary_MYSQL_TYPE_YEAR
    const Protocol_MYSQL_TYPE_NEWDATE = 0x0e;//	see Protocol_MYSQL_TYPE_DATE
    const Protocol_MYSQL_TYPE_VARCHAR = 0x0f;//	Implemented by ProtocolBinary_MYSQL_TYPE_VARCHAR
    const Protocol_MYSQL_TYPE_BIT = 0x10;//	Implemented by ProtocolBinary_MYSQL_TYPE_BIT
    const Protocol_MYSQL_TYPE_TIMESTAMP2 = 0x11;//	see Protocol_MYSQL_TYPE_TIMESTAMP
    const Protocol_MYSQL_TYPE_DATETIME2 = 0x12;//	see Protocol_MYSQL_TYPE_DATETIME
    const Protocol_MYSQL_TYPE_TIME2 = 0x13;//	see Protocol_MYSQL_TYPE_TIME
    const Protocol_MYSQL_TYPE_NEWDECIMAL = 0xf6;//	Implemented by ProtocolBinary_MYSQL_TYPE_NEWDECIMAL
    const Protocol_MYSQL_TYPE_ENUM = 0xf7;//	Implemented by ProtocolBinary_MYSQL_TYPE_ENUM
    const Protocol_MYSQL_TYPE_SET = 0xf8;//	Implemented by ProtocolBinary_MYSQL_TYPE_SET
    const Protocol_MYSQL_TYPE_TINY_BLOB = 0xf9;//	Implemented by ProtocolBinary_MYSQL_TYPE_TINY_BLOB
    const Protocol_MYSQL_TYPE_MEDIUM_BLOB = 0xfa;//	Implemented by ProtocolBinary_MYSQL_TYPE_MEDIUM_BLOB
    const Protocol_MYSQL_TYPE_LONG_BLOB = 0xfb;//	Implemented by ProtocolBinary_MYSQL_TYPE_LONG_BLOB
    const Protocol_MYSQL_TYPE_BLOB = 0xfc;//	Implemented by ProtocolBinary_MYSQL_TYPE_BLOB
    const Protocol_MYSQL_TYPE_VAR_STRING = 0xfd;//	Implemented by ProtocolBinary_MYSQL_TYPE_VAR_STRING
    const Protocol_MYSQL_TYPE_STRING = 0xfe;//	Implemented by ProtocolBinary_MYSQL_TYPE_STRING
    const Protocol_MYSQL_TYPE_GEOMETRY = 0xff;//

    public static function getTypeName($type)
    {
        switch ($type) {
            case self::Protocol_MYSQL_TYPE_DECIMAL:
                return 'DECIMAL';
            case self::Protocol_MYSQL_TYPE_TINY:
                return 'TINY';
            case self::Protocol_MYSQL_TYPE_SHORT:
                return 'SHORT';
            case self::Protocol_MYSQL_TYPE_LONG:
                return 'LONG';
            case self::Protocol_MYSQL_TYPE_FLOAT:
                return 'FLOAT';
            case self::Protocol_MYSQL_TYPE_DOUBLE:
                return 'DOUBLE';
            case self::Protocol_MYSQL_TYPE_NULL:
                return 'NULL';
            case self::Protocol_MYSQL_TYPE_TIMESTAMP:
                return 'TIMESTAMP';
            case self::Protocol_MYSQL_TYPE_LONGLONG:
                return 'LONGLONG';
            case self::Protocol_MYSQL_TYPE_INT24:
                return 'INT24';
            case self::Protocol_MYSQL_TYPE_DATE:
                return 'DATE';
            case self::Protocol_MYSQL_TYPE_TIME:
                return 'TIME';
            case self::Protocol_MYSQL_TYPE_DATETIME:
                return 'DATETIME';
            case self::Protocol_MYSQL_TYPE_YEAR:
                return 'YEAR';
            case self::Protocol_MYSQL_TYPE_NEWDATE:
                return 'NEWDATE';
            case self::Protocol_MYSQL_TYPE_VARCHAR:
                return 'VARCHAR';
            case self::Protocol_MYSQL_TYPE_BIT:
                return 'BIT';
            case self::Protocol_MYSQL_TYPE_TIMESTAMP2:
                return 'TIMESTAMP2';
            case self::Protocol_MYSQL_TYPE_DATETIME2:
                return 'DATETIME2';
            case self::Protocol_MYSQL_TYPE_TIME2:
                return 'TIME2';
            case self::Protocol_MYSQL_TYPE_NEWDECIMAL:
                return 'NEWDECIMAL';
            case self::Protocol_MYSQL_TYPE_ENUM:
                return 'ENUM';
            case self::Protocol_MYSQL_TYPE_SET:
                return 'SET';
            case self::Protocol_MYSQL_TYPE_TINY_BLOB:
                return 'TINY_BLOB';
            case self::Protocol_MYSQL_TYPE_MEDIUM_BLOB:
                return 'MEDIUM_BLOB';
            case self::Protocol_MYSQL_TYPE_LONG_BLOB:
                return 'LONG_BLOB';
            case self::Protocol_MYSQL_TYPE_BLOB:
                return 'BLOB';
            case self::Protocol_MYSQL_TYPE_VAR_STRING:
                return 'VAR_STRING';
            case self::Protocol_MYSQL_TYPE_STRING:
                return 'STRING';
            case self::Protocol_MYSQL_TYPE_GEOMETRY:
                return 'GEOMETRY';
            default:
                return 'UNKNOWN';
        }
    }
}