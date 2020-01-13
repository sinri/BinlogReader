<?php


namespace sinri\BinlogReader\entity;


use Exception;
use sinri\BinlogReader\BinlogReader;

class BinlogV4EventHeaderEntity
{
    const TYPE_UNKNOWN_EVENT=0x00;
    const TYPE_START_EVENT_V3=0x01;
    const TYPE_QUERY_EVENT=0x02;
    const TYPE_STOP_EVENT=0x03;
    const TYPE_ROTATE_EVENT=0x04;
    const TYPE_INTVAR_EVENT=0x05;
    const TYPE_LOAD_EVENT=0x06;
    const TYPE_SLAVE_EVENT=0x07;
    const TYPE_CREATE_FILE_EVENT=0x08;
    const TYPE_APPEND_BLOCK_EVENT=0x09;
    const TYPE_EXEC_LOAD_EVENT=0x0a;
    const TYPE_DELETE_FILE_EVENT=0x0b;
    const TYPE_NEW_LOAD_EVENT=0x0c;
    const TYPE_RAND_EVENT=0x0d;
    const TYPE_USER_VAR_EVENT=0x0e;
    const TYPE_FORMAT_DESCRIPTION_EVENT=0x0f;
    const TYPE_XID_EVENT=0x10;
    const TYPE_BEGIN_LOAD_QUERY_EVENT=0x11;
    const TYPE_EXECUTE_LOAD_QUERY_EVENT=0x12;
    const TYPE_TABLE_MAP_EVENT=0x13;
    const TYPE_WRITE_ROWS_EVENT_V0=0x14;
    const TYPE_UPDATE_ROWS_EVENT_V0=0x15;
    const TYPE_DELETE_ROWS_EVENT_V0=0x16;
    const TYPE_WRITE_ROWS_EVENT_V1=0x17;
    const TYPE_UPDATE_ROWS_EVENT_V1=0x18;
    const TYPE_DELETE_ROWS_EVENT_V1=0x19;
    const TYPE_INCIDENT_EVENT=0x1a;
    const TYPE_HEARTBEAT_EVENT=0x1b;
    const TYPE_IGNORABLE_EVENT=0x1c;
    const TYPE_ROWS_QUERY_EVENT=0x1d;
    const TYPE_WRITE_ROWS_EVENT_V2=0x1e;
    const TYPE_UPDATE_ROWS_EVENT_V2=0x1f;
    const TYPE_DELETE_ROWS_EVENT_V2=0x20;
    const TYPE_GTID_EVENT=0x21;
    const TYPE_ANONYMOUS_GTID_EVENT=0x22;
    const TYPE_PREVIOUS_GTIDS_EVENT=0x23;

    protected static $typeNameDictionary=[
        self::TYPE_UNKNOWN_EVENT=>'TYPE_UNKNOWN_EVENT:0x00',
        self::TYPE_START_EVENT_V3=>'TYPE_START_EVENT_V3:0x01',
        self::TYPE_QUERY_EVENT=>'TYPE_QUERY_EVENT:0x02',
        self::TYPE_STOP_EVENT=>'TYPE_STOP_EVENT:0x03',
        self::TYPE_ROTATE_EVENT=>'TYPE_ROTATE_EVENT:0x04',
        self::TYPE_INTVAR_EVENT=>'TYPE_INTVAR_EVENT:0x05',
        self::TYPE_LOAD_EVENT=>'TYPE_LOAD_EVENT:0x06',
        self::TYPE_SLAVE_EVENT=>'TYPE_SLAVE_EVENT:0x07',
        self::TYPE_CREATE_FILE_EVENT=>'TYPE_CREATE_FILE_EVENT:0x08',
        self::TYPE_APPEND_BLOCK_EVENT=>'TYPE_APPEND_BLOCK_EVENT:0x09',
        self::TYPE_EXEC_LOAD_EVENT=>'TYPE_EXEC_LOAD_EVENT:0x0a',
        self::TYPE_DELETE_FILE_EVENT=>'TYPE_DELETE_FILE_EVENT:0x0b',
        self::TYPE_NEW_LOAD_EVENT=>'TYPE_NEW_LOAD_EVENT:0x0c',
        self::TYPE_RAND_EVENT=>'TYPE_RAND_EVENT:0x0d',
        self::TYPE_USER_VAR_EVENT=>'TYPE_USER_VAR_EVENT:0x0e',
        self::TYPE_FORMAT_DESCRIPTION_EVENT=>'TYPE_FORMAT_DESCRIPTION_EVENT:0x0f',
        self::TYPE_XID_EVENT=>'TYPE_XID_EVENT:0x10',
        self::TYPE_BEGIN_LOAD_QUERY_EVENT=>'TYPE_BEGIN_LOAD_QUERY_EVENT:0x11',
        self::TYPE_EXECUTE_LOAD_QUERY_EVENT=>'TYPE_EXECUTE_LOAD_QUERY_EVENT:0x12',
        self::TYPE_TABLE_MAP_EVENT=>'TYPE_TABLE_MAP_EVENT:0x13',
        self::TYPE_WRITE_ROWS_EVENT_V0=>'TYPE_WRITE_ROWS_EVENT_V0:0x14',
        self::TYPE_UPDATE_ROWS_EVENT_V0=>'TYPE_UPDATE_ROWS_EVENT_V0:0x15',
        self::TYPE_DELETE_ROWS_EVENT_V0=>'TYPE_DELETE_ROWS_EVENT_V0:0x16',
        self::TYPE_WRITE_ROWS_EVENT_V1=>'TYPE_WRITE_ROWS_EVENT_V1:0x17',
        self::TYPE_UPDATE_ROWS_EVENT_V1=>'TYPE_UPDATE_ROWS_EVENT_V1:0x18',
        self::TYPE_DELETE_ROWS_EVENT_V1=>'TYPE_DELETE_ROWS_EVENT_V1:0x19',
        self::TYPE_INCIDENT_EVENT=>'TYPE_INCIDENT_EVENT:0x1a',
        self::TYPE_HEARTBEAT_EVENT=>'TYPE_HEARTBEAT_EVENT:0x1b',
        self::TYPE_IGNORABLE_EVENT=>'TYPE_IGNORABLE_EVENT:0x1c',
        self::TYPE_ROWS_QUERY_EVENT=>'TYPE_ROWS_QUERY_EVENT:0x1d',
        self::TYPE_WRITE_ROWS_EVENT_V2=>'TYPE_WRITE_ROWS_EVENT_V2:0x1e',
        self::TYPE_UPDATE_ROWS_EVENT_V2=>'TYPE_UPDATE_ROWS_EVENT_V2:0x1f',
        self::TYPE_DELETE_ROWS_EVENT_V2=>'TYPE_DELETE_ROWS_EVENT_V2:0x20',
        self::TYPE_GTID_EVENT=>'TYPE_GTID_EVENT:0x21',
        self::TYPE_ANONYMOUS_GTID_EVENT=>'TYPE_ANONYMOUS_GTID_EVENT:0x22',
        self::TYPE_PREVIOUS_GTIDS_EVENT=>'TYPE_PREVIOUS_GTIDS_EVENT:0x23',
    ];

    public $timestamp;
    public $typeCode;
    public $serverId;
    public $eventLength;
    public $nextPosition;
    public $flags;

    /**
     * @param BinlogReader $reader
     * @return BinlogV4EventHeaderEntity
     * @throws Exception
     */
    public function readFromBinlogStream($reader){
        $this->timestamp = $reader->readNumber(4);
        $this->typeCode = $reader->readNumber(1);
        $this->serverId = $reader->readNumber(4);
        $this->eventLength = $reader->readNumber(4);
        $this->nextPosition = $reader->readNumber(4);
        $this->flags = $reader->readNumber(2);

        return $this;
    }

    public function getTypeName(){
        return self::$typeNameDictionary[$this->typeCode];
    }

    public static function parseTypeName($typeCode){
        return self::$typeNameDictionary[$typeCode];
    }

    public function __toString()
    {
        return "Timestamp: ".$this->timestamp.' ('.date('Y-m-d H:i:s',$this->timestamp).') '
            ."Type: 0x".str_pad(dechex($this->typeCode),2,'0',STR_PAD_LEFT).' '.$this->getTypeName().PHP_EOL
            ."Server ID: ".$this->serverId." Event Length: ".$this->eventLength." Next Position: ".$this->nextPosition.PHP_EOL
            ."Flags: 0x".str_pad(dechex($this->typeCode),4,'0',STR_PAD_LEFT);
    }
}