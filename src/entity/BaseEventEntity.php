<?php


namespace sinri\BinlogReader\entity;


use Exception;
use sinri\BinlogReader\BRByteBuffer;
use sinri\BinlogReader\BREnv;
use sinri\BinlogReader\BRKit;

abstract class BaseEventEntity
{
    /**
     * @var BinlogV4EventHeaderEntity
     */
    public $header;
    /**
     * @var int a mysterious tail 4 bytes may be this
     */
    public $checksum;
    /**
     * @var BRByteBuffer
     */
    protected $bodyBuffer;

    /**
     * BaseEventEntity constructor.
     * @param BinlogV4EventHeaderEntity $header
     * @param BRByteBuffer $bodyBuffer
     * @param int $checksum
     */
    public function __construct($header, $bodyBuffer, $checksum)
    {
        $this->header = $header;
        $this->bodyBuffer = $bodyBuffer;
        $this->checksum = $checksum;
    }

    public function __toString()
    {
        //json_encode($this,JSON_PRETTY_PRINT);
        return "=== Event Header ===" . PHP_EOL
            . $this->header->__toString() . PHP_EOL
            . "--- Event   Body ---" . PHP_EOL
            . $this->getHumanReadableDescription() . PHP_EOL
            . "=== Event   End === CRC32: " . BRKit::hexOneNumber($this->checksum, 4);

    }

    /**
     * @throws Exception
     */
    protected function debugShowBody()
    {
        $bodyLength = $this->bodyBuffer->getSize();
        BREnv::getLogger()->logInline("Debug Show Body ↓");
        for ($i = 0; $i < $bodyLength; $i++) {
            if ($i % 10 == 0) {
                BREnv::getLogger()->logInline(PHP_EOL . str_pad($i, 6, "0", STR_PAD_LEFT) . "\t| ");
            }
            $x = $this->bodyBuffer->readNumberWithSomeBytesLE($i, 1);
            BREnv::getLogger()->logInline(str_pad(dechex($x), 2, '0', STR_PAD_LEFT) . '(' . chr($x) . ') ');
        }
        BREnv::getLogger()->logInline(PHP_EOL);
    }

    /**
     * @return string
     */
    abstract public function getHumanReadableDescription();

    /**
     * @throws Exception
     */
    abstract public function parseBodyBuffer();

    /**
     * @param BinlogV4EventHeaderEntity $header
     * @param BRByteBuffer $bodyBuffer
     * @param int $checksum
     * @return bool|BaseEventEntity
     * @throws Exception
     */
    public static function parseNextEvent($header, $bodyBuffer, $checksum)
    {
        $entity = false;

        switch ($header->typeCode) {
            case BinlogV4EventHeaderEntity::TYPE_FORMAT_DESCRIPTION_EVENT:
                $entity = new FormatDescriptionEventEntity($header, $bodyBuffer, $checksum);
                break;
            case BinlogV4EventHeaderEntity::TYPE_PREVIOUS_GTIDS_EVENT:
                $entity = new PreviousGTIDSEventEntity($header, $bodyBuffer, $checksum);
                break;
            case BinlogV4EventHeaderEntity::TYPE_GTID_EVENT:
                $entity = new GTIDEventEntity($header, $bodyBuffer, $checksum);
                break;
            case BinlogV4EventHeaderEntity::TYPE_QUERY_EVENT:
                $entity = new QueryEventEntity($header, $bodyBuffer, $checksum);
                break;
            case BinlogV4EventHeaderEntity::TYPE_TABLE_MAP_EVENT:
                $entity = new TableMapEventEntity($header, $bodyBuffer, $checksum);
                break;
            case BinlogV4EventHeaderEntity::TYPE_WRITE_ROWS_EVENT_V0:
                $entity = new RowsEventEntity($header, $bodyBuffer, $checksum);
                $entity->version = RowsEventEntity::VERSION_0;
                $entity->method = RowsEventEntity::TYPE_WRITE;
                break;
            case BinlogV4EventHeaderEntity::TYPE_UPDATE_ROWS_EVENT_V0:
                $entity = new RowsEventEntity($header, $bodyBuffer, $checksum);
                $entity->version = RowsEventEntity::VERSION_0;
                $entity->method = RowsEventEntity::TYPE_UPDATE;
                break;
            case BinlogV4EventHeaderEntity::TYPE_DELETE_ROWS_EVENT_V0:
                $entity = new RowsEventEntity($header, $bodyBuffer, $checksum);
                $entity->version = RowsEventEntity::VERSION_0;
                $entity->method = RowsEventEntity::TYPE_DELETE;
                break;
            case BinlogV4EventHeaderEntity::TYPE_WRITE_ROWS_EVENT_V1:
                $entity = new RowsEventEntity($header, $bodyBuffer, $checksum);
                $entity->version = RowsEventEntity::VERSION_1;
                $entity->method = RowsEventEntity::TYPE_WRITE;
                break;
            case BinlogV4EventHeaderEntity::TYPE_UPDATE_ROWS_EVENT_V1:
                $entity = new RowsEventEntity($header, $bodyBuffer, $checksum);
                $entity->version = RowsEventEntity::VERSION_1;
                $entity->method = RowsEventEntity::TYPE_UPDATE;
                break;
            case BinlogV4EventHeaderEntity::TYPE_DELETE_ROWS_EVENT_V1:
                $entity = new RowsEventEntity($header, $bodyBuffer, $checksum);
                $entity->version = RowsEventEntity::VERSION_1;
                $entity->method = RowsEventEntity::TYPE_DELETE;
                break;
            case BinlogV4EventHeaderEntity::TYPE_WRITE_ROWS_EVENT_V2:
                $entity = new RowsEventEntity($header, $bodyBuffer, $checksum);
                $entity->version = RowsEventEntity::VERSION_2;
                $entity->method = RowsEventEntity::TYPE_WRITE;
                break;
            case BinlogV4EventHeaderEntity::TYPE_UPDATE_ROWS_EVENT_V2:
                $entity = new RowsEventEntity($header, $bodyBuffer, $checksum);
                $entity->version = RowsEventEntity::VERSION_2;
                $entity->method = RowsEventEntity::TYPE_UPDATE;
                break;
            case BinlogV4EventHeaderEntity::TYPE_DELETE_ROWS_EVENT_V2:
                $entity = new RowsEventEntity($header, $bodyBuffer, $checksum);
                $entity->version = RowsEventEntity::VERSION_2;
                $entity->method = RowsEventEntity::TYPE_DELETE;
                break;
            case BinlogV4EventHeaderEntity::TYPE_XID_EVENT:
                $entity = new XIDEventEntity($header, $bodyBuffer, $checksum);
                break;

            case BinlogV4EventHeaderEntity::TYPE_START_EVENT_V3:
            case BinlogV4EventHeaderEntity::TYPE_STOP_EVENT:
            case BinlogV4EventHeaderEntity::TYPE_ROTATE_EVENT:
            case BinlogV4EventHeaderEntity::TYPE_INTVAR_EVENT:
            case BinlogV4EventHeaderEntity::TYPE_LOAD_EVENT:
            case BinlogV4EventHeaderEntity::TYPE_SLAVE_EVENT:
            case BinlogV4EventHeaderEntity::TYPE_CREATE_FILE_EVENT:
            case BinlogV4EventHeaderEntity::TYPE_APPEND_BLOCK_EVENT:
            case BinlogV4EventHeaderEntity::TYPE_EXEC_LOAD_EVENT:
            case BinlogV4EventHeaderEntity::TYPE_DELETE_FILE_EVENT:
            case BinlogV4EventHeaderEntity::TYPE_NEW_LOAD_EVENT:
            case BinlogV4EventHeaderEntity::TYPE_RAND_EVENT:

            case BinlogV4EventHeaderEntity::TYPE_BEGIN_LOAD_QUERY_EVENT:
            case BinlogV4EventHeaderEntity::TYPE_EXECUTE_LOAD_QUERY_EVENT:

            case BinlogV4EventHeaderEntity::TYPE_INCIDENT_EVENT:
            case BinlogV4EventHeaderEntity::TYPE_HEARTBEAT_EVENT:
            case BinlogV4EventHeaderEntity::TYPE_ROWS_QUERY_EVENT:

            case BinlogV4EventHeaderEntity::TYPE_ANONYMOUS_GTID_EVENT:
            case BinlogV4EventHeaderEntity::TYPE_USER_VAR_EVENT:
                BREnv::getLogger()->error("Unknown Event Type", ['header' => $header]);
                throw new Exception("Unknown Type " . $header->typeCode . '(0x' . dechex($header->typeCode) . ')');
            case BinlogV4EventHeaderEntity::TYPE_IGNORABLE_EVENT:
            case BinlogV4EventHeaderEntity::TYPE_UNKNOWN_EVENT:
                $entity = new IgnoredEventEntity($header, $bodyBuffer, $checksum);
                break;
            default:
                BREnv::getLogger()->error("Unknown Event Type: 0x" . dechex($header->typeCode), ['type code' => $header->typeCode]);
                return false;
        }
        $entity->parseBodyBuffer();

        if ($entity->header->typeCode == BinlogV4EventHeaderEntity::TYPE_FORMAT_DESCRIPTION_EVENT) {
            self::$currentFormatDescriptionEventEntity = $entity;
        } elseif ($entity->header->typeCode == BinlogV4EventHeaderEntity::TYPE_TABLE_MAP_EVENT) {
            self::$tableMap[$entity->tableId] = $entity;
        }
        return $entity;
    }

    /**
     * @var FormatDescriptionEventEntity
     */
    public static $currentFormatDescriptionEventEntity;
    /**
     * @var TableMapEventEntity[]
     */
    public static $tableMap = [];
}