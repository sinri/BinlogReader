<?php


namespace sinri\BinlogReader\entity;


use Exception;
use sinri\BinlogReader\BREnv;
use sinri\BinlogReader\BRKit;

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
    public $schema;
    public $query;

    /**
     * @inheritDoc
     */
    public function getHumanReadableDescription()
    {
        return "Slave Proxy ID: " . $this->slaveProxyId . ' '
            . 'Execution Time: ' . BRKit::refinedTime($this->executionTime) . ' '
            . 'Error Code: ' . $this->errorCode . PHP_EOL
            . 'Schema: ' . $this->schema . PHP_EOL
            . 'Query: ' . $this->query;
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