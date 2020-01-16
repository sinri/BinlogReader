<?php


namespace sinri\BinlogReader\entity;


use Exception;
use sinri\ark\core\ArkHelper;
use sinri\BinlogReader\BREnv;
use sinri\BinlogReader\BRKit;

/**
 * Binlog::QUERY_EVENT
 * The query event is used to send text querys right the binlog.
 * @see https://dev.mysql.com/doc/internals/en/query-event.html
 *
 * Class QueryEventEntity
 * @package sinri\BinlogReader\entity
 */
class QueryEventEntity extends BaseEventEntity
{
    const STATUS_VAR_KEY_Q_FLAGS2_CODE = 0x00;
    const STATUS_VAR_KEY_Q_SQL_MODE_CODE = 0x01;
    const STATUS_VAR_KEY_Q_CATALOG = 0x02;
    const STATUS_VAR_KEY_Q_AUTO_INCREMENT = 0x03;
    const STATUS_VAR_KEY_Q_CHARSET_CODE = 0x04;
    const STATUS_VAR_KEY_Q_TIME_ZONE_CODE = 0x05;
    const STATUS_VAR_KEY_Q_CATALOG_NZ_CODE = 0x06;
    const STATUS_VAR_KEY_Q_LC_TIME_NAMES_CODE = 0x07;
    const STATUS_VAR_KEY_Q_CHARSET_DATABASE_CODE = 0x08;
    const STATUS_VAR_KEY_Q_TABLE_MAP_FOR_UPDATE_CODE = 0x09;
    const STATUS_VAR_KEY_Q_MASTER_DATA_WRITTEN_CODE = 0x0a;
    const STATUS_VAR_KEY_Q_INVOKERS = 0x0b;
    const STATUS_VAR_KEY_Q_UPDATED_DB_NAMES = 0x0c;
    const STATUS_VAR_KEY_Q_MICROSECONDS = 0x0d;

    const OPTION_AUTO_IS_NULL = 0x00004000;
    const OPTION_NOT_AUTOCOMMIT = 0x00080000;
    const OPTION_NO_FOREIGN_KEY_CHECKS = 0x04000000;
    const OPTION_RELAXED_UNIQUE_CHECKS = 0x08000000;

    const MODE_REAL_AS_FLOAT = 0x00000001;
    const MODE_PIPES_AS_CONCAT = 0x00000002;
    const MODE_ANSI_QUOTES = 0x00000004;
    const MODE_IGNORE_SPACE = 0x00000008;
    const MODE_NOT_USED = 0x00000010;
    const MODE_ONLY_FULL_GROUP_BY = 0x00000020;
    const MODE_NO_UNSIGNED_SUBTRACTION = 0x00000040;
    const MODE_NO_DIR_IN_CREATE = 0x00000080;
    const MODE_POSTGRESQL = 0x00000100;
    const MODE_ORACLE = 0x00000200;
    const MODE_MSSQL = 0x00000400;
    const MODE_DB2 = 0x00000800;
    const MODE_MAXDB = 0x00001000;
    const MODE_NO_KEY_OPTIONS = 0x00002000;
    const MODE_NO_TABLE_OPTIONS = 0x00004000;
    const MODE_NO_FIELD_OPTIONS = 0x00008000;
    const MODE_MYSQL323 = 0x00010000;
    const MODE_MYSQL40 = 0x00020000;
    const MODE_ANSI = 0x00040000;
    const MODE_NO_AUTO_VALUE_ON_ZERO = 0x00080000;
    const MODE_NO_BACKSLASH_ESCAPES = 0x00100000;
    const MODE_STRICT_TRANS_TABLES = 0x00200000;
    const MODE_STRICT_ALL_TABLES = 0x00400000;
    const MODE_NO_ZERO_IN_DATE = 0x00800000;
    const MODE_NO_ZERO_DATE = 0x01000000;
    const MODE_INVALID_DATES = 0x02000000;
    const MODE_ERROR_FOR_DIVISION_BY_ZERO = 0x04000000;
    const MODE_TRADITIONAL = 0x08000000;
    const MODE_NO_AUTO_CREATE_USER = 0x10000000;
    const MODE_HIGH_NOT_PRECEDENCE = 0x20000000;
    const MODE_NO_ENGINE_SUBSTITUTION = 0x40000000;
    const MODE_PAD_CHAR_TO_FULL_LENGTH = 0x80000000;
    public $slaveProxyId;

    public $executionTime;
    public $schemaLength;
    public $errorCode;
    /**
     * @var int number of bytes in the following sequence of status-vars
     */
    public $statusVarsLength;
    /**
     * the definition of items @see https://dev.mysql.com/doc/internals/en/query-event.html
     * @var array status key-value pair
     */
    public $statusVars;
    /**
     * @var array
     */
    public $parsedStatusVariables;

    public $schema;
    public $query;

    /**
     * @inheritDoc
     */
    public function getHumanReadableDescription()
    {
        $s = "Slave Proxy ID: " . $this->slaveProxyId . ' '
            . 'Execution Time: ' . BRKit::refinedTime($this->executionTime) . ' '
            . 'Error Code: ' . $this->errorCode . PHP_EOL;
        $s .= "Status Variables:" . PHP_EOL;
        foreach ($this->parsedStatusVariables as $parsedStatusVariableKey => $parsedStatusVariableValue) {
            if ($parsedStatusVariableValue === false) continue;
            $s .= "> $parsedStatusVariableKey : $parsedStatusVariableValue" . PHP_EOL;
        }
        $s .= 'Schema: ' . $this->schema . PHP_EOL
            . 'Query: ' . $this->query;
        return $s;
    }

    /**
     * @inheritDoc
     */
    public function parseBodyBuffer()
    {
        $this->slaveProxyId = $this->bodyBuffer->readNumberWithSomeBytesLE(0, 4);//$reader->readNumber(4);
        $this->executionTime = $this->bodyBuffer->readNumberWithSomeBytesLE(4, 4);//$reader->readNumber(4);
        $this->schemaLength = $this->bodyBuffer->readNumberWithSomeBytesLE(8, 1);//$reader->readNumber(1);
        $this->errorCode = $this->bodyBuffer->readNumberWithSomeBytesLE(9, 2);//$reader->readNumber(2);
        $this->statusVarsLength = $this->bodyBuffer->readNumberWithSomeBytesLE(11, 2);//$reader->readNumber(2);

        $offset = 13;
        $realStatusBytes = 0;
        while ($realStatusBytes < $this->statusVarsLength) {
            $key = $this->bodyBuffer->readNumberWithSomeBytesLE($offset + $realStatusBytes, 1);//$reader->readNumber(1);
            $realStatusBytes += 1;
            switch ($key) {
                case self::STATUS_VAR_KEY_Q_FLAGS2_CODE:
                    // Bitmask of flags that are usually set with SET:
                case self::STATUS_VAR_KEY_Q_MASTER_DATA_WRITTEN_CODE:
                    $value = $this->bodyBuffer->readNumberWithSomeBytesLE($offset + $realStatusBytes, 4);//$reader->readNumber(4);
                    $realStatusBytes += 4;
                    break;
                case self::STATUS_VAR_KEY_Q_SQL_MODE_CODE:
                    // Bitmask of flags that are usually set with SET sql_mode:
                case self::STATUS_VAR_KEY_Q_TABLE_MAP_FOR_UPDATE_CODE:
                    // a 64bit-field ... should only be used in Row Based Replication and multi-table updates
                    $value = $this->bodyBuffer->readNumberWithSomeBytesLE($offset + $realStatusBytes, 8);//$reader->readNumber(8);
                    $realStatusBytes += 8;
                    break;
                case self::STATUS_VAR_KEY_Q_CATALOG:
                    $catalogLength = $this->bodyBuffer->readNumberWithSomeBytesLE($offset + $realStatusBytes, 1);//$reader->readNumber(1);
                    $realStatusBytes += 1;
                    $value = $this->bodyBuffer->readString($offset + $realStatusBytes, $catalogLength + 1);//$reader->readString($catalogLength);
                    $realStatusBytes += $catalogLength + 1;
                    //$zeroChar=$this->bodyBuffer->readNumberWithSomeBytesLE($offset+$realStatusBytes,1);//$reader->readNumber(1);// \0 as EOF
                    //$realStatusBytes+=1;
                    break;
                case self::STATUS_VAR_KEY_Q_AUTO_INCREMENT:
                    $value = [
                        'autoincrement-increment' => $this->bodyBuffer->readNumberWithSomeBytesLE($offset + $realStatusBytes, 2),//$reader->readNumber(2),
                        'autoincrement-offset' => $this->bodyBuffer->readNumberWithSomeBytesLE($offset + $realStatusBytes + 2, 2),//$reader->readNumber(2),
                    ];
                    $realStatusBytes += 4;
                    break;
                case self::STATUS_VAR_KEY_Q_CHARSET_CODE:
                    // @see Connection Character Sets and Collations https://dev.mysql.com/doc/refman/8.0/en/charset-connection.html
                    $value = [
                        'character_set_client' => $this->bodyBuffer->readNumberWithSomeBytesLE($offset + $realStatusBytes, 2),//$reader->readNumber(2),
                        'collation_connection' => $this->bodyBuffer->readNumberWithSomeBytesLE($offset + $realStatusBytes + 2, 2),//$reader->readNumber(2),
                        'collation_server' => $this->bodyBuffer->readNumberWithSomeBytesLE($offset + $realStatusBytes + 4, 2),//$reader->readNumber(2),
                    ];
                    $realStatusBytes += 6;
                    break;
                case self::STATUS_VAR_KEY_Q_TIME_ZONE_CODE:
                    //@see MySQL Server Time Zone Support https://dev.mysql.com/doc/refman/8.0/en/time-zone-support.html
                    $timezoneLength = $this->bodyBuffer->readNumberWithSomeBytesLE($offset + $realStatusBytes, 1);//$reader->readNumber(1);
                    $realStatusBytes += 1;
                    $value = $this->bodyBuffer->readString($offset + $realStatusBytes, $timezoneLength);//$reader->readString($timezoneLength);
                    $realStatusBytes += $timezoneLength;
                    break;
                case self::STATUS_VAR_KEY_Q_CATALOG_NZ_CODE:
                    $categoryNZCodeLength = $this->bodyBuffer->readNumberWithSomeBytesLE($offset + $realStatusBytes, 1);//$reader->readNumber(1);
                    $realStatusBytes += 1;
                    $value = $this->bodyBuffer->readString($offset + $realStatusBytes, $categoryNZCodeLength);//$reader->readString($categoryNZCodeLength);
                    $realStatusBytes += $categoryNZCodeLength;
                    break;
                case self::STATUS_VAR_KEY_Q_LC_TIME_NAMES_CODE:
                case self::STATUS_VAR_KEY_Q_CHARSET_DATABASE_CODE:
                    $value = $this->bodyBuffer->readNumberWithSomeBytesLE($offset + $realStatusBytes, 2);//$reader->readNumber(2);
                    $realStatusBytes += 2;
                    break;
                case self::STATUS_VAR_KEY_Q_INVOKERS:
                    $usernameLength = $this->bodyBuffer->readNumberWithSomeBytesLE($offset + $realStatusBytes, 1);//$reader->readNumber(1);
                    $realStatusBytes += 1;
                    $username = $this->bodyBuffer->readString($offset + $realStatusBytes, $usernameLength);//$reader->readString($usernameLength);
                    $realStatusBytes += $usernameLength;
                    $hostnameLength = $this->bodyBuffer->readNumberWithSomeBytesLE($offset + $realStatusBytes, 1);//$reader->readNumber(1);
                    $realStatusBytes += 1;
                    $hostname = $this->bodyBuffer->readString($offset + $realStatusBytes, $hostnameLength);//$reader->readString($hostnameLength);
                    $realStatusBytes += $hostnameLength;
                    $value = [
                        'username' => $username,
                        'hostname' => $hostname,
                    ];
                    break;
                case self::STATUS_VAR_KEY_Q_UPDATED_DB_NAMES:
                    $dbNameLength = $this->bodyBuffer->readNumberWithSomeBytesLE($offset + $realStatusBytes, 1);//$reader->readNumber(1);
                    $realStatusBytes += 1;
                    $value = "";
                    for ($i = 0; $i < $dbNameLength; $i++) {
                        $value .= $this->bodyBuffer->readStringEndedWithZero($offset + $realStatusBytes, $d);//$reader->readStringEndedWithZero();
                        $realStatusBytes += $d;//strlen($value);
                    }
                    break;
                case self::STATUS_VAR_KEY_Q_MICROSECONDS:
                    $value = $this->bodyBuffer->readNumberWithSomeBytesLE($offset + $realStatusBytes, 3);//$reader->readNumber(3);
                    $realStatusBytes += 3;
                    break;
                default:
                    throw new Exception(__METHOD__ . '@' . __LINE__ . ' invalid status var key ' . $key);
            }
            $this->statusVars[$key] = $value;
        }

        $offset += $realStatusBytes;

        foreach ($this->statusVars as $key => $value) {
            switch ($key) {
                case self::STATUS_VAR_KEY_Q_FLAGS2_CODE:
                    $this->parsedStatusVariables['SQL_AUTO_IS_NULL'] = (($value & self::OPTION_AUTO_IS_NULL) > 0);
                    $this->parsedStatusVariables['OPTION_NOT_AUTOCOMMIT'] = (($value & self::OPTION_NOT_AUTOCOMMIT) > 0);
                    $this->parsedStatusVariables['OPTION_NO_FOREIGN_KEY_CHECKS'] = (($value & self::OPTION_NO_FOREIGN_KEY_CHECKS) > 0);
                    $this->parsedStatusVariables['OPTION_RELAXED_UNIQUE_CHECKS'] = (($value & self::OPTION_RELAXED_UNIQUE_CHECKS) > 0);
                    break;
                case self::STATUS_VAR_KEY_Q_SQL_MODE_CODE:
                    $this->parsedStatusVariables['MODE_REAL_AS_FLOAT'] = (($value & self::MODE_REAL_AS_FLOAT) > 0);
                    $this->parsedStatusVariables['MODE_PIPES_AS_CONCAT'] = (($value & self::MODE_PIPES_AS_CONCAT) > 0);
                    $this->parsedStatusVariables['MODE_ANSI_QUOTES'] = (($value & self::MODE_ANSI_QUOTES) > 0);
                    $this->parsedStatusVariables['MODE_IGNORE_SPACE'] = (($value & self::MODE_IGNORE_SPACE) > 0);
                    $this->parsedStatusVariables['MODE_NOT_USED'] = (($value & self::MODE_NOT_USED) > 0);
                    $this->parsedStatusVariables['MODE_ONLY_FULL_GROUP_BY'] = (($value & self::MODE_ONLY_FULL_GROUP_BY) > 0);
                    $this->parsedStatusVariables['MODE_NO_UNSIGNED_SUBTRACTION'] = (($value & self::MODE_NO_UNSIGNED_SUBTRACTION) > 0);
                    $this->parsedStatusVariables['MODE_NO_DIR_IN_CREATE'] = (($value & self::MODE_NO_DIR_IN_CREATE) > 0);
                    $this->parsedStatusVariables['MODE_POSTGRESQL'] = (($value & self::MODE_POSTGRESQL) > 0);
                    $this->parsedStatusVariables['MODE_ORACLE'] = (($value & self::MODE_ORACLE) > 0);
                    $this->parsedStatusVariables['MODE_MSSQL'] = (($value & self::MODE_MSSQL) > 0);
                    $this->parsedStatusVariables['MODE_DB2'] = (($value & self::MODE_DB2) > 0);
                    $this->parsedStatusVariables['MODE_MAXDB'] = (($value & self::MODE_MAXDB) > 0);
                    $this->parsedStatusVariables['MODE_NO_KEY_OPTIONS'] = (($value & self::MODE_NO_KEY_OPTIONS) > 0);
                    $this->parsedStatusVariables['MODE_NO_TABLE_OPTIONS'] = (($value & self::MODE_NO_TABLE_OPTIONS) > 0);
                    $this->parsedStatusVariables['MODE_NO_FIELD_OPTIONS'] = (($value & self::MODE_NO_FIELD_OPTIONS) > 0);
                    $this->parsedStatusVariables['MODE_MYSQL323'] = (($value & self::MODE_MYSQL323) > 0);
                    $this->parsedStatusVariables['MODE_MYSQL40'] = (($value & self::MODE_MYSQL40) > 0);
                    $this->parsedStatusVariables['MODE_ANSI'] = (($value & self::MODE_ANSI) > 0);
                    $this->parsedStatusVariables['MODE_NO_AUTO_VALUE_ON_ZERO'] = (($value & self::MODE_NO_AUTO_VALUE_ON_ZERO) > 0);
                    $this->parsedStatusVariables['MODE_NO_BACKSLASH_ESCAPES'] = (($value & self::MODE_NO_BACKSLASH_ESCAPES) > 0);
                    $this->parsedStatusVariables['MODE_STRICT_TRANS_TABLES'] = (($value & self::MODE_STRICT_TRANS_TABLES) > 0);
                    $this->parsedStatusVariables['MODE_STRICT_ALL_TABLES'] = (($value & self::MODE_STRICT_ALL_TABLES) > 0);
                    $this->parsedStatusVariables['MODE_NO_ZERO_IN_DATE'] = (($value & self::MODE_NO_ZERO_IN_DATE) > 0);
                    $this->parsedStatusVariables['MODE_NO_ZERO_DATE'] = (($value & self::MODE_NO_ZERO_DATE) > 0);
                    $this->parsedStatusVariables['MODE_INVALID_DATES'] = (($value & self::MODE_INVALID_DATES) > 0);
                    $this->parsedStatusVariables['MODE_ERROR_FOR_DIVISION_BY_ZERO'] = (($value & self::MODE_ERROR_FOR_DIVISION_BY_ZERO) > 0);
                    $this->parsedStatusVariables['MODE_TRADITIONAL'] = (($value & self::MODE_TRADITIONAL) > 0);
                    $this->parsedStatusVariables['MODE_NO_AUTO_CREATE_USER'] = (($value & self::MODE_NO_AUTO_CREATE_USER) > 0);
                    $this->parsedStatusVariables['MODE_HIGH_NOT_PRECEDENCE'] = (($value & self::MODE_HIGH_NOT_PRECEDENCE) > 0);
                    $this->parsedStatusVariables['MODE_NO_ENGINE_SUBSTITUTION'] = (($value & self::MODE_NO_ENGINE_SUBSTITUTION) > 0);
                    $this->parsedStatusVariables['MODE_PAD_CHAR_TO_FULL_LENGTH'] = (($value & self::MODE_PAD_CHAR_TO_FULL_LENGTH) > 0);
                    break;
                case self::STATUS_VAR_KEY_Q_AUTO_INCREMENT:
                    $this->parsedStatusVariables['autoincrement_increment'] = ArkHelper::readTarget($value, ['autoincrement-increment']);
                    $this->parsedStatusVariables['autoincrement_offset'] = ArkHelper::readTarget($value, ['autoincrement-offset']);
                    break;
                case self::STATUS_VAR_KEY_Q_CATALOG:
                    $this->parsedStatusVariables['catalog_name'] = $value;
                    break;
                case self::STATUS_VAR_KEY_Q_CHARSET_CODE:
                    $this->parsedStatusVariables['character_set_client'] = ArkHelper::readTarget($value, ['character_set_client']);
                    $this->parsedStatusVariables['collation_connection'] = ArkHelper::readTarget($value, ['collation_connection']);
                    $this->parsedStatusVariables['collation_server'] = ArkHelper::readTarget($value, ['collation_server']);
                    break;
                case self::STATUS_VAR_KEY_Q_TIME_ZONE_CODE:
                    $this->parsedStatusVariables['timezone_code'] = $value;
                    break;
                case self::STATUS_VAR_KEY_Q_CATALOG_NZ_CODE:
                    $this->parsedStatusVariables['catalog_nz_name'] = $value;
                    break;
                case self::STATUS_VAR_KEY_Q_LC_TIME_NAMES_CODE:
                    // LC_TIME of the server. Defines how to parse week-, month and day-names in timestamps.
                    $this->parsedStatusVariables['lc_time_name_code'] = $value;
                    break;
                case self::STATUS_VAR_KEY_Q_CHARSET_DATABASE_CODE:
                    $this->parsedStatusVariables['charset_database_code'] = $value;
                    break;
                case self::STATUS_VAR_KEY_Q_TABLE_MAP_FOR_UPDATE_CODE:
                    $this->parsedStatusVariables['table_map_for_update_code'] = $value;
                    break;
                case self::STATUS_VAR_KEY_Q_MASTER_DATA_WRITTEN_CODE:
                    $this->parsedStatusVariables['master_data_written_code'] = $value;
                    break;
                case self::STATUS_VAR_KEY_Q_INVOKERS:
                    $this->parsedStatusVariables['invokers_username'] = ArkHelper::readTarget($value, ['username']);
                    $this->parsedStatusVariables['invokers_hostname'] = ArkHelper::readTarget($value, ['hostname']);
                    break;
                case self::STATUS_VAR_KEY_Q_UPDATED_DB_NAMES:
                    $this->parsedStatusVariables['updated_db_names'] = $value;
                    break;
                case self::STATUS_VAR_KEY_Q_MICROSECONDS:
                    $this->parsedStatusVariables['microseconds'] = $value;
                    break;
            }
        }

        if ($this->schemaLength > 0) {
            $this->schema = $this->bodyBuffer->readString($offset, $this->schemaLength);//$reader->readString($this->schemaLength);
            $offset += $this->schemaLength;
        }

        $offset += 1;// 0x00
        //$reader->readNumber(1);
        //$reader->getLogger()->debug(__METHOD__.'@'.__LINE__." temp=".$temp.' <-- must be 0x00');

        $this->query = '';
        $queryLength = $this->header->eventLength - 19 - $this->schemaLength - $realStatusBytes - 1 - 4 - 4 - 1 - 2 - 2;
        $queryLength -= BREnv::checksumByteCount();// for tail crc32
        if ($queryLength > 0) {
            //$this->query = base64_encode($reader->readString($queryLength));
            // "BEGIN|0xf0||0xec||0xb1||0xb8|" the last four bytes strange => should verify if crc32 4 bytes

            $this->query = $this->bodyBuffer->readString($offset, $queryLength);

//            for ($i=0;$i<$queryLength;$i++){
//                $charOrd=$this->bodyBuffer->readNumberWithSomeBytesLE($offset,1);//$reader->readNumber(1);
//                $offset+=1;
//                if($charOrd<128) {
//                    $char = chr($charOrd);
//                    $this->query .= $char;
//                }else{
//                    $this->query .= "|0x".dechex($charOrd)."|";
//                }
//            }
        }
    }
}