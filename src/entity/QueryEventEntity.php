<?php


namespace sinri\BinlogReader\entity;


use sinri\BinlogReader\BinlogReader;
use sinri\BinlogReader\BRKit;

class QueryEventEntity extends BaseBinlogV4EventEntity
{

    const STATUS_VAR_KEY_Q_FLAGS2_CODE=0x00;
    const STATUS_VAR_KEY_Q_SQL_MODE_CODE=0x01;
    const STATUS_VAR_KEY_Q_CATALOG=0x02;
    const STATUS_VAR_KEY_Q_AUTO_INCREMENT=0x03;
    const STATUS_VAR_KEY_Q_CHARSET_CODE=0x04;
    const STATUS_VAR_KEY_Q_TIME_ZONE_CODE=0x05;
    const STATUS_VAR_KEY_Q_CATALOG_NZ_CODE=0x06;
    const STATUS_VAR_KEY_Q_LC_TIME_NAMES_CODE=0x07;
    const STATUS_VAR_KEY_Q_CHARSET_DATABASE_CODE=0x08;
    const STATUS_VAR_KEY_Q_TABLE_MAP_FOR_UPDATE_CODE=0x09;
    const STATUS_VAR_KEY_Q_MASTER_DATA_WRITTEN_CODE=0x0a;
    const STATUS_VAR_KEY_Q_INVOKERS=0x0b;
    const STATUS_VAR_KEY_Q_UPDATED_DB_NAMES=0x0c;
    const STATUS_VAR_KEY_Q_MICROSECONDS=0x0d;

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
    public function readFromBinlogStream($reader)
    {
        $this->slaveProxyId=$reader->readNumber(4);
        $this->executionTime=$reader->readNumber(4);
        $this->schemaLength=$reader->readNumber(1);
        $this->errorCode=$reader->readNumber(2);
        $this->statusVarsLength=$reader->readNumber(2);

//        var_dump($this->statusVarsLength);

        $realStatusBytes=0;
        while($realStatusBytes<$this->statusVarsLength){
            $key=$reader->readNumber(1);
            $realStatusBytes+=1;
            switch ($key) {
                case self::STATUS_VAR_KEY_Q_FLAGS2_CODE:
                    // Bitmask of flags that are usually set with SET:
                case self::STATUS_VAR_KEY_Q_MASTER_DATA_WRITTEN_CODE:
                    $value = $reader->readNumber(4);
                    $realStatusBytes+=4;
                    break;
                case self::STATUS_VAR_KEY_Q_SQL_MODE_CODE:
                    // Bitmask of flags that are usually set with SET sql_mode:
                case self::STATUS_VAR_KEY_Q_TABLE_MAP_FOR_UPDATE_CODE:
                    // a 64bit-field ... should only be used in Row Based Replication and multi-table updates
                    $value = $reader->readNumber(8);
                    $realStatusBytes+=8;
                    break;
                case self::STATUS_VAR_KEY_Q_CATALOG:
                    $catalogLength=$reader->readNumber(1);
                    $catalogName=$reader->readString($catalogLength);
                    $zeroChar=$reader->readNumber(1);// \0 as EOF
                    $value=$catalogName;
                    $realStatusBytes+=1+$catalogLength+1;
                    break;
                case self::STATUS_VAR_KEY_Q_AUTO_INCREMENT:
                    $value=[
                        'autoincrement-increment'=>$reader->readNumber(2),
                        'autoincrement-offset'=>$reader->readNumber(2),
                    ];
                    $realStatusBytes+=4;
                    break;
                case self::STATUS_VAR_KEY_Q_CHARSET_CODE:
                    // @see Connection Character Sets and Collations https://dev.mysql.com/doc/refman/8.0/en/charset-connection.html
                    $value=[
                        'character_set_client'=>$reader->readNumber(2),
                        'collation_connection'=>$reader->readNumber(2),
                        'collation_server'=>$reader->readNumber(2),
                    ];
                    $realStatusBytes+=6;
                    break;
                case self::STATUS_VAR_KEY_Q_TIME_ZONE_CODE:
                    //@see MySQL Server Time Zone Support https://dev.mysql.com/doc/refman/8.0/en/time-zone-support.html
                    $timezoneLength=$reader->readNumber(1);
                    $value=$reader->readString($timezoneLength);
                    $realStatusBytes+=1+$timezoneLength;
                    break;
                case self::STATUS_VAR_KEY_Q_CATALOG_NZ_CODE:
                    $categoryNZCodeLength=$reader->readNumber(1);
                    $value=$reader->readString($categoryNZCodeLength);
                    $realStatusBytes+=1+$categoryNZCodeLength;
                    break;
                case self::STATUS_VAR_KEY_Q_LC_TIME_NAMES_CODE:
                case self::STATUS_VAR_KEY_Q_CHARSET_DATABASE_CODE:
                    $value = $reader->readNumber(2);
                    $realStatusBytes+=2;
                    break;
                case self::STATUS_VAR_KEY_Q_INVOKERS:
                    $usernameLength=$reader->readNumber(1);
                    $username=$reader->readString($usernameLength);
                    $hostnameLength=$reader->readNumber(1);
                    $hostname=$reader->readString($hostnameLength);
                    $value=[
                        'username'=>$username,
                        'hostname'=>$hostname,
                    ];
                    $realStatusBytes+=1+$usernameLength+1+$hostnameLength;
                    break;
                case self::STATUS_VAR_KEY_Q_UPDATED_DB_NAMES:
                    $dbNameLength=$reader->readNumber(1);
                    $value=$reader->readString($dbNameLength);
                    $realStatusBytes+=1+$dbNameLength;
                    break;
                case self::STATUS_VAR_KEY_Q_MICROSECONDS:
                    $value = $reader->readNumber(3);
                    $realStatusBytes+=3;
                    break;
                default:
                    throw new \Exception(__METHOD__.'@'.__LINE__.' invalid status var key '.$key);
            }
//            var_dump($key);
//            var_dump($value);
            $this->statusVars[$key]=$value;
        }

        if($this->schemaLength>0) {
            $this->schema = $reader->readString($this->schemaLength);
        }

        $reader->readNumber(1);

        $this->query='';
        $queryLength=$this->header->eventLength-19-$this->schemaLength-$realStatusBytes-1-4-4-1-2-2;
        $queryLength-=self::checksumByteCount();// for tail crc32
        if($queryLength>0) {
            //$this->query = base64_encode($reader->readString($queryLength));
            // TODO "BEGIN|0xf0||0xec||0xb1||0xb8|" the last four bytes strange => should verify if crc32 4 bytes

            for ($i=0;$i<$queryLength;$i++){
                $charOrd=$reader->readNumber(1);
                if($charOrd<128) {
                    $char = chr($charOrd);
                    $this->query .= $char;
                }else{
                    $this->query .= "|0x".dechex($charOrd)."|";
                }
            }
        }

        //var_dump($this);
    }

    public function getHumanReadableDescription()
    {
        return "Slave Proxy ID: ".$this->slaveProxyId.' '
            .'Execution Time: '.BRKit::refinedTime($this->executionTime).' '
            .'Error Code: '.$this->errorCode.PHP_EOL
            .'Schema: '.$this->schema.PHP_EOL
            .'Query: '.$this->query;
    }
}