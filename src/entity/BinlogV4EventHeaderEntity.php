<?php


namespace sinri\BinlogReader\entity;


use Exception;
use sinri\BinlogReader\BRByteBuffer;

/**
 *
 * @see https://dev.mysql.com/doc/internals/en/binlog-event-header.html
 * For the flags: @see https://dev.mysql.com/doc/internals/en/binlog-event-flag.html
 * For the event types, @see https://dev.mysql.com/doc/internals/en/binlog-event-type.html
 * For each by category, @see https://dev.mysql.com/doc/internals/en/binlog-event.html
 * And for each detail, @see https://dev.mysql.com/doc/internals/en/event-data-for-specific-event-types.html
 *
 * Class BinlogV4EventHeaderEntity
 * @package sinri\BinlogReader\entity
 */
class BinlogV4EventHeaderEntity
{
    const EVENT_HEADER_BYTE_COUNT = 19;

    const TYPE_UNKNOWN_EVENT = 0x00;
    const TYPE_START_EVENT_V3 = 0x01;
    const TYPE_QUERY_EVENT = 0x02;
    const TYPE_STOP_EVENT = 0x03;
    const TYPE_ROTATE_EVENT = 0x04;
    const TYPE_INTVAR_EVENT = 0x05;
    const TYPE_LOAD_EVENT = 0x06;
    const TYPE_SLAVE_EVENT = 0x07;
    const TYPE_CREATE_FILE_EVENT = 0x08;
    const TYPE_APPEND_BLOCK_EVENT = 0x09;
    const TYPE_EXEC_LOAD_EVENT = 0x0a;
    const TYPE_DELETE_FILE_EVENT = 0x0b;
    const TYPE_NEW_LOAD_EVENT = 0x0c;
    const TYPE_RAND_EVENT = 0x0d;
    const TYPE_USER_VAR_EVENT = 0x0e;
    const TYPE_FORMAT_DESCRIPTION_EVENT = 0x0f;
    const TYPE_XID_EVENT = 0x10;
    const TYPE_BEGIN_LOAD_QUERY_EVENT = 0x11;
    const TYPE_EXECUTE_LOAD_QUERY_EVENT = 0x12;
    const TYPE_TABLE_MAP_EVENT = 0x13;
    const TYPE_WRITE_ROWS_EVENT_V0 = 0x14;
    const TYPE_UPDATE_ROWS_EVENT_V0 = 0x15;
    const TYPE_DELETE_ROWS_EVENT_V0 = 0x16;
    const TYPE_WRITE_ROWS_EVENT_V1 = 0x17;
    const TYPE_UPDATE_ROWS_EVENT_V1 = 0x18;
    const TYPE_DELETE_ROWS_EVENT_V1 = 0x19;
    const TYPE_INCIDENT_EVENT = 0x1a;
    const TYPE_HEARTBEAT_EVENT = 0x1b;
    const TYPE_IGNORABLE_EVENT = 0x1c;
    const TYPE_ROWS_QUERY_EVENT = 0x1d;
    const TYPE_WRITE_ROWS_EVENT_V2 = 0x1e;
    const TYPE_UPDATE_ROWS_EVENT_V2 = 0x1f;
    const TYPE_DELETE_ROWS_EVENT_V2 = 0x20;
    const TYPE_GTID_EVENT = 0x21;
    const TYPE_ANONYMOUS_GTID_EVENT = 0x22;
    const TYPE_PREVIOUS_GTIDS_EVENT = 0x23;
    // the beneath added in MySQL 5.7
    const TYPE_TRANSACTION_CONTEXT_EVENT = 0x24;
    const TYPE_VIEW_CHANGE_EVENT = 0x25;
    const TYPE_XA_PREPARE_LOG_EVENT = 0x26;// like XID
    // const TYPE_ENUM_END_EVENT=?;


    const FLAG_LOG_EVENT_BINLOG_IN_USE_F = 0x0001;
    const FLAG_LOG_EVENT_FORCED_ROTATE_F = 0x0002;
    const FLAG_LOG_EVENT_THREAD_SPECIFIC_F = 0x0004;
    const FLAG_LOG_EVENT_SUPPRESS_USE_F = 0x0008;
    const FLAG_LOG_EVENT_UPDATE_TABLE_MAP_VERSION_F = 0x0010;
    const FLAG_LOG_EVENT_ARTIFICIAL_F = 0x0020;
    const FLAG_LOG_EVENT_RELAY_LOG_F = 0x0040;
    const FLAG_LOG_EVENT_IGNORABLE_F = 0x0080;
    const FLAG_LOG_EVENT_NO_FILTER_F = 0x0100;
    const FLAG_LOG_EVENT_MTS_ISOLATE_F = 0x0200;

    protected static $typeNameDictionary = [
        self::TYPE_UNKNOWN_EVENT => 'TYPE_UNKNOWN_EVENT:0x00',
        self::TYPE_START_EVENT_V3 => 'TYPE_START_EVENT_V3:0x01',
        self::TYPE_QUERY_EVENT => 'TYPE_QUERY_EVENT:0x02',
        self::TYPE_STOP_EVENT => 'TYPE_STOP_EVENT:0x03',
        self::TYPE_ROTATE_EVENT => 'TYPE_ROTATE_EVENT:0x04',
        self::TYPE_INTVAR_EVENT => 'TYPE_INTVAR_EVENT:0x05',
        self::TYPE_LOAD_EVENT => 'TYPE_LOAD_EVENT:0x06',
        self::TYPE_SLAVE_EVENT => 'TYPE_SLAVE_EVENT:0x07',
        self::TYPE_CREATE_FILE_EVENT => 'TYPE_CREATE_FILE_EVENT:0x08',
        self::TYPE_APPEND_BLOCK_EVENT => 'TYPE_APPEND_BLOCK_EVENT:0x09',
        self::TYPE_EXEC_LOAD_EVENT => 'TYPE_EXEC_LOAD_EVENT:0x0a',
        self::TYPE_DELETE_FILE_EVENT => 'TYPE_DELETE_FILE_EVENT:0x0b',
        self::TYPE_NEW_LOAD_EVENT => 'TYPE_NEW_LOAD_EVENT:0x0c',
        self::TYPE_RAND_EVENT => 'TYPE_RAND_EVENT:0x0d',
        self::TYPE_USER_VAR_EVENT => 'TYPE_USER_VAR_EVENT:0x0e',
        self::TYPE_FORMAT_DESCRIPTION_EVENT => 'TYPE_FORMAT_DESCRIPTION_EVENT:0x0f',
        self::TYPE_XID_EVENT => 'TYPE_XID_EVENT:0x10',
        self::TYPE_BEGIN_LOAD_QUERY_EVENT => 'TYPE_BEGIN_LOAD_QUERY_EVENT:0x11',
        self::TYPE_EXECUTE_LOAD_QUERY_EVENT => 'TYPE_EXECUTE_LOAD_QUERY_EVENT:0x12',
        self::TYPE_TABLE_MAP_EVENT => 'TYPE_TABLE_MAP_EVENT:0x13',
        self::TYPE_WRITE_ROWS_EVENT_V0 => 'TYPE_WRITE_ROWS_EVENT_V0:0x14',
        self::TYPE_UPDATE_ROWS_EVENT_V0 => 'TYPE_UPDATE_ROWS_EVENT_V0:0x15',
        self::TYPE_DELETE_ROWS_EVENT_V0 => 'TYPE_DELETE_ROWS_EVENT_V0:0x16',
        self::TYPE_WRITE_ROWS_EVENT_V1 => 'TYPE_WRITE_ROWS_EVENT_V1:0x17',
        self::TYPE_UPDATE_ROWS_EVENT_V1 => 'TYPE_UPDATE_ROWS_EVENT_V1:0x18',
        self::TYPE_DELETE_ROWS_EVENT_V1 => 'TYPE_DELETE_ROWS_EVENT_V1:0x19',
        self::TYPE_INCIDENT_EVENT => 'TYPE_INCIDENT_EVENT:0x1a',
        self::TYPE_HEARTBEAT_EVENT => 'TYPE_HEARTBEAT_EVENT:0x1b',
        self::TYPE_IGNORABLE_EVENT => 'TYPE_IGNORABLE_EVENT:0x1c',
        self::TYPE_ROWS_QUERY_EVENT => 'TYPE_ROWS_QUERY_EVENT:0x1d',
        self::TYPE_WRITE_ROWS_EVENT_V2 => 'TYPE_WRITE_ROWS_EVENT_V2:0x1e',
        self::TYPE_UPDATE_ROWS_EVENT_V2 => 'TYPE_UPDATE_ROWS_EVENT_V2:0x1f',
        self::TYPE_DELETE_ROWS_EVENT_V2 => 'TYPE_DELETE_ROWS_EVENT_V2:0x20',
        self::TYPE_GTID_EVENT => 'TYPE_GTID_EVENT:0x21',
        self::TYPE_ANONYMOUS_GTID_EVENT => 'TYPE_ANONYMOUS_GTID_EVENT:0x22',
        self::TYPE_PREVIOUS_GTIDS_EVENT => 'TYPE_PREVIOUS_GTIDS_EVENT:0x23',
        self::TYPE_TRANSACTION_CONTEXT_EVENT => 'TYPE_TRANSACTION_CONTEXT_EVENT:0x24',
        self::TYPE_VIEW_CHANGE_EVENT => 'TYPE_VIEW_CHANGE_EVENT:0x25',
        self::TYPE_XA_PREPARE_LOG_EVENT => 'TYPE_XA_PREPARE_LOG_EVENT:0x26',
    ];

    public $timestamp;
    public $typeCode;
    public $serverId;
    public $eventLength;
    public $nextPosition;
    public $flagsValue;
    public $flags;

    /**
     * BinlogV4EventHeaderEntity constructor.
     * @param BRByteBuffer $headerBuffer
     * @throws Exception
     */
    public function __construct($headerBuffer)
    {
        $this->timestamp = $headerBuffer->readNumberWithSomeBytesLE(0, 4);
        $this->typeCode = $headerBuffer->readNumberWithSomeBytesLE(4, 1);
        $this->serverId = $headerBuffer->readNumberWithSomeBytesLE(5, 4);
        $this->eventLength = $headerBuffer->readNumberWithSomeBytesLE(9, 4);
        $this->nextPosition = $headerBuffer->readNumberWithSomeBytesLE(13, 4);
        $this->flagsValue = $headerBuffer->readNumberWithSomeBytesLE(17, 2);

        $flagMasks = self::getFlagMasks();
        foreach ($flagMasks as $flagMask => $flagMaskDesc) {
            $this->flags[$flagMask] = $this->flagsValue & $flagMask;
        }

    }

    protected static $flagMasks = null;

    public static function getFlagMasks()
    {
        if (self::$flagMasks === null) {
            self::$flagMasks = [
                self::FLAG_LOG_EVENT_BINLOG_IN_USE_F => 'Flag-Binlog-In-Use',
                self::FLAG_LOG_EVENT_FORCED_ROTATE_F => 'Flag-Forced-Rotate',
                self::FLAG_LOG_EVENT_THREAD_SPECIFIC_F => 'Flag-Thread-Specific (CREATE TEMPORARY TABLE ...)',
                self::FLAG_LOG_EVENT_SUPPRESS_USE_F => 'Flag-Suppress-Use-Schema (CREATE DATABASE, ...)',
                self::FLAG_LOG_EVENT_UPDATE_TABLE_MAP_VERSION_F => 'Flag-Update-Table-Map-Version',
                self::FLAG_LOG_EVENT_ARTIFICIAL_F => 'Flag-Artificial (Slave Only)',
                self::FLAG_LOG_EVENT_RELAY_LOG_F => 'Flag-Relay-Log (For Slave)',
                self::FLAG_LOG_EVENT_IGNORABLE_F => 'Flag-Ignorable',
                self::FLAG_LOG_EVENT_NO_FILTER_F => 'Flag-No-Filter',
                self::FLAG_LOG_EVENT_MTS_ISOLATE_F => 'Flag-MTS-Isolate',
            ];
        }
        return self::$flagMasks;
    }

    public function getTypeName()
    {
        return self::$typeNameDictionary[$this->typeCode];
    }

    public static function parseTypeName($typeCode)
    {
        return self::$typeNameDictionary[$typeCode];
    }

    public function __toString()
    {
        $s = "Timestamp: " . $this->timestamp . ' (' . date('Y-m-d H:i:s', $this->timestamp) . ') '
            . "Type: 0x" . str_pad(dechex($this->typeCode), 2, '0', STR_PAD_LEFT) . ' ' . $this->getTypeName() . PHP_EOL
            . "Server ID: " . $this->serverId . " Event Length: " . $this->eventLength . " Next Position: " . $this->nextPosition . PHP_EOL
            . "Flags: 0x" . str_pad(dechex($this->flagsValue), 4, '0', STR_PAD_LEFT) . PHP_EOL;
        $flagMasks = self::getFlagMasks();
        foreach ($flagMasks as $flagMask => $flagMaskDesc) {
            if ($this->flags[$flagMask]) {
                $s .= "[ON] " . $flagMaskDesc . PHP_EOL;
            }
        }
        return $s;
    }

}