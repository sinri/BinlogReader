<?php


namespace sinri\BinlogReader\entity;


use Exception;
use sinri\BinlogReader\BinlogReader;
use sinri\BinlogReader\BRKit;

abstract class BaseBinlogV4EventEntity
{
    const CHECKSUM_NONE='NONE';
    const CHECKSUM_CRC32='CRC32';

    static protected $checksumMode=self::CHECKSUM_CRC32;

    /**
     * @var BinlogV4EventHeaderEntity
     */
    public $header;
    /**
     * @var int a mysterious tail 4 bytes may be this
     */
    public $crc32value;

    /**
     * BaseBinlogV4EventEntity constructor.
     * @param BinlogV4EventHeaderEntity $header
     */
    public function __construct($header)
    {
        $this->header=$header;
    }

    public static function checksumByteCount(){
        switch (self::$checksumMode){
            case self::CHECKSUM_CRC32:
                return 4;
            default:
                return 0;
        }
    }

    /**
     * @return string
     */
    public static function getChecksumMode(): string
    {
        return self::$checksumMode;
    }

    /**
     * @param string $checksumMode
     */
    public static function setChecksumMode(string $checksumMode)
    {
        self::$checksumMode = $checksumMode;
    }

    public function __toString()
    {
        //json_encode($this,JSON_PRETTY_PRINT);
        return "=== Event Header ===".PHP_EOL
            .$this->header->__toString().PHP_EOL
            ."--- Event   Body ---".PHP_EOL
            .$this->getHumanReadableDescription().PHP_EOL
            ."=== Event   End === CRC32: ".BRKit::hexOneNumber($this->crc32value,4);

    }

    abstract public function getHumanReadableDescription();

    /**
     * @param BinlogReader $reader
     */
    abstract public function readFromBinlogStream($reader);

    /**
     * @param BinlogReader $reader
     * @throws Exception
     */
    public final function tryReadChecksum($reader){
        $this->crc32value=$reader->readCrc32Tail($this->header->nextPosition);
    }

    /**
     * @param BinlogReader $reader
     * @throws Exception
     */
    protected function debugShowBody($reader){
        $bodyLength=$this->header->eventLength-19;
        $reader->getLogger()->logInline("Debug Show Body ↓");
        for($i=0;$i<$bodyLength;$i++){
            if($i%10==0){
                $reader->getLogger()->logInline(PHP_EOL.str_pad($i,6,"0",STR_PAD_LEFT)."\t| ");
            }
            $x=$reader->readNumber(1);
            $reader->getLogger()->logInline(str_pad(dechex($x),2,'0',STR_PAD_LEFT).'('.chr($x).') ');
        }
        $reader->getLogger()->logInline(PHP_EOL);
    }

    /**
     * @param BinlogReader $reader
     * @return BaseBinlogV4EventEntity|false
     * @throws Exception
     */
    public static function parseNextEvent(BinlogReader $reader){
        $entity=false;

        $header=new BinlogV4EventHeaderEntity();
        $header->readFromBinlogStream($reader);

        switch ($header->typeCode) {
            case BinlogV4EventHeaderEntity::TYPE_FORMAT_DESCRIPTION_EVENT:
                $entity = new FormatDescriptionEventEntity($header);
                break;
            case BinlogV4EventHeaderEntity::TYPE_PREVIOUS_GTIDS_EVENT:
                $entity = new PreviousGTIDSEventEntity($header);
                break;
            case BinlogV4EventHeaderEntity::TYPE_GTID_EVENT:
                $entity = new GTIDEventEntity($header);
                break;
            case BinlogV4EventHeaderEntity::TYPE_QUERY_EVENT:
                $entity = new QueryEventEntity($header);
                break;
            case BinlogV4EventHeaderEntity::TYPE_TABLE_MAP_EVENT:
                $entity = new TableMapEventEntity($header);
                break;
            case BinlogV4EventHeaderEntity::TYPE_WRITE_ROWS_EVENT_V0:
                $entity = new RowsEventEntity($header);
                $entity->version = RowsEventEntity::VERSION_0;
                $entity->method = RowsEventEntity::TYPE_WRITE;
                break;
            case BinlogV4EventHeaderEntity::TYPE_UPDATE_ROWS_EVENT_V0:
                $entity = new RowsEventEntity($header);
                $entity->version = RowsEventEntity::VERSION_0;
                $entity->method = RowsEventEntity::TYPE_UPDATE;
                break;
            case BinlogV4EventHeaderEntity::TYPE_DELETE_ROWS_EVENT_V0:
                $entity = new RowsEventEntity($header);
                $entity->version = RowsEventEntity::VERSION_0;
                $entity->method = RowsEventEntity::TYPE_DELETE;
                break;
            case BinlogV4EventHeaderEntity::TYPE_WRITE_ROWS_EVENT_V1:
                $entity = new RowsEventEntity($header);
                $entity->version = RowsEventEntity::VERSION_1;
                $entity->method = RowsEventEntity::TYPE_WRITE;
                break;
            case BinlogV4EventHeaderEntity::TYPE_UPDATE_ROWS_EVENT_V1:
                $entity = new RowsEventEntity($header);
                $entity->version = RowsEventEntity::VERSION_1;
                $entity->method = RowsEventEntity::TYPE_UPDATE;
                break;
            case BinlogV4EventHeaderEntity::TYPE_DELETE_ROWS_EVENT_V1:
                $entity = new RowsEventEntity($header);
                $entity->version = RowsEventEntity::VERSION_1;
                $entity->method = RowsEventEntity::TYPE_DELETE;
                break;
            case BinlogV4EventHeaderEntity::TYPE_WRITE_ROWS_EVENT_V2:
                $entity = new RowsEventEntity($header);
                $entity->version = RowsEventEntity::VERSION_2;
                $entity->method = RowsEventEntity::TYPE_WRITE;
                break;
            case BinlogV4EventHeaderEntity::TYPE_UPDATE_ROWS_EVENT_V2:
                $entity = new RowsEventEntity($header);
                $entity->version = RowsEventEntity::VERSION_2;
                $entity->method = RowsEventEntity::TYPE_UPDATE;
                break;
            case BinlogV4EventHeaderEntity::TYPE_DELETE_ROWS_EVENT_V2:
                $entity = new RowsEventEntity($header);
                $entity->version = RowsEventEntity::VERSION_2;
                $entity->method = RowsEventEntity::TYPE_DELETE;
                break;
            case BinlogV4EventHeaderEntity::TYPE_XID_EVENT:
                $entity = new XIDEventEntity($header);
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
                $reader->getLogger()->error("Unknown Event Type", ['header' => $header]);
                throw new Exception("Unknown Type " . $header->typeCode . '(0x' . dechex($header->typeCode) . ')');
            case BinlogV4EventHeaderEntity::TYPE_IGNORABLE_EVENT:
            case BinlogV4EventHeaderEntity::TYPE_UNKNOWN_EVENT:
                $entity = new IgnoredEventEntity($header);
                break;
            default:
                $reader->getLogger()->error("Unknown Event Type: 0x" . dechex($header->typeCode), ['type code' => $header->typeCode]);
                return false;
        }
        $entity->readFromBinlogStream($reader);
        $entity->tryReadChecksum($reader);
        return $entity;
    }
}