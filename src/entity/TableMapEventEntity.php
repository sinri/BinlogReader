<?php


namespace sinri\BinlogReader\entity;


use sinri\BinlogReader\BRByteBuffer;
use sinri\BinlogReader\BRKit;

/**
 * The first event used in Row Based Replication declares how a table that is about to be changed is defined.
 * The TABLE_MAP_EVENT defines the structure if the tables that are about to be changed.
 * @see https://dev.mysql.com/doc/internals/en/table-map-event.html
 *
 * Class TableMapEventEntity
 * @package sinri\BinlogReader\entity
 */
class TableMapEventEntity extends BaseEventEntity
{
    public $tableId;
    public $flags;

    public $schemaNameLength;
    public $schemaName;
    public $tableNameLength;
    public $tableName;
    /**
     * @var int
     */
    public $columnCount;
    /**
     * @var int[] [len=column_count] array of column definitions, one byte per field type
     * @see https://dev.mysql.com/doc/internals/en/com-query-response.html#packet-Protocol::ColumnType
     */
    public $columnTypeDef;
    /**
     * @var BRByteBuffer[] array of metainfo per column, length is the overall length of the metainfo-array in bytes, the length of each metainfo field is dependent on the columns field type
     */
    public $columnMetaDef;
    /**
     * @var BRByteBuffer
     */
    public $nullBitmap;

    protected $postHeaderLen;

    public function __construct($header, $bodyBuffer, $checksum)
    {
        parent::__construct($header, $bodyBuffer, $checksum);
        $this->postHeaderLen = (self::$currentFormatDescriptionEventEntity->postHeaderLengthForAllEventTypes[$header->typeCode]);
    }

    /**
     * @inheritDoc
     */
    public function getHumanReadableDescription()
    {
        $s = "Table ID: " . $this->tableId . " " . $this->schemaName . ' . ' . $this->tableName . PHP_EOL
            . "Flags: " . $this->flags . " Column Count: " . $this->columnCount . PHP_EOL;
        $s .= "Table Column Types: " . PHP_EOL;
        for ($i = 0; $i < $this->columnCount; $i++) {
            $s .= "[$i] " . BRKit::hexOneNumber($this->columnTypeDef[$i])
                . ':' . TableColumnTypeProtocol::getTypeName($this->columnTypeDef[$i])
                . (isset($this->columnMetaDef[$i]) ? (' Meta: ' . $this->columnMetaDef[$i]->showAsInlineHexForNumberLE()) : '')
                . PHP_EOL;

        }
        //$s.="Column Meta Def: ".BRKit::hexInlineNumbers($this->columnMetaDef).PHP_EOL;
        $s .= "Null Bit Map: " . $this->nullBitmap->showAsBitmap($this->columnCount);
        return $s;
    }

    /**
     * @inheritDoc
     */
    public function parseBodyBuffer()
    {
        // $post_header_len -> $this->postHeaderLen
        //$post_header_len=$this->getPostHeaderLen();//$reader->getFormatDescriptionEventEntity()->postHeaderLengthForAllEventTypes[$this->header->typeCode];
        //var_dump($post_header_len);
        $offset = 0;

        if ($this->postHeaderLen == 6) {
            $this->tableId = $this->bodyBuffer->readNumberWithSomeBytesLE($offset, 4);//$reader->readNumber(4);
            $offset += 4;
        } else {
            $this->tableId = $this->bodyBuffer->readNumberWithSomeBytesLE($offset, 6);//$reader->readNumber(6);
            $offset += 6;
        }
        $this->flags = $this->bodyBuffer->readNumberWithSomeBytesLE($offset, 2);//$reader->readNumber(2);
        $offset += 2;

        $this->schemaNameLength = $this->bodyBuffer->readNumberWithSomeBytesLE($offset, 1);//$reader->readNumber(1);
        $offset += 1;
        $this->schemaName = $this->bodyBuffer->readString($offset, $this->schemaNameLength + 1);//$reader->readString($this->schemaNameLength);
        //$reader->readNumber(1);// 0x00
        $offset += $this->schemaNameLength + 1;
        $this->tableNameLength = $this->bodyBuffer->readNumberWithSomeBytesLE($offset, 1);//$reader->readNumber(1);
        $offset += 1;
        $this->tableName = $this->bodyBuffer->readString($offset, $this->tableNameLength + 1);//$reader->readString($this->tableNameLength);
        //$reader->readNumber(1);// 0x00
        $offset += $this->tableNameLength + 1;

        $this->columnCount = $this->bodyBuffer->readLenencInt($offset, $tempLength);//$reader->readLenencInt();
        $offset += $tempLength;
        for ($i = 0; $i < $this->columnCount; $i++) {
            $this->columnTypeDef[$i] = $this->bodyBuffer->readNumberWithSomeBytesLE($offset, 1);//$reader->readNumber(1);
            $offset += 1;
        }

        //$this->columnMetaDef=$reader->readLenencString();
        //$columnMetaDefBytes=$reader->readNumber(1);
        // TODO need to understand this byte
        // Packed integer. The length of the metadata block.
        //$offset += 1;
        $this->bodyBuffer->readLenencInt($offset, $tempLength);
        $offset += $tempLength;
//        for($i=0;$i<$columnMetaDefBytes;$i++){
//            $this->columnMetaDef[]=$reader->readNumber(1);
//        }
        for ($i = 0; $i < $this->columnCount; $i++) {
            // TODO the length definition @see https://dev.mysql.com/doc/internals/en/table-map-event.html
            // not fulfilled yet!
            switch ($this->columnTypeDef[$i]) {
                case TableColumnTypeProtocol::Protocol_MYSQL_TYPE_STRING:
                case TableColumnTypeProtocol::Protocol_MYSQL_TYPE_VAR_STRING:
                case TableColumnTypeProtocol::Protocol_MYSQL_TYPE_VARCHAR:
                case TableColumnTypeProtocol::Protocol_MYSQL_TYPE_DECIMAL:
                case TableColumnTypeProtocol::Protocol_MYSQL_TYPE_NEWDECIMAL:
                case TableColumnTypeProtocol::Protocol_MYSQL_TYPE_ENUM:
                case TableColumnTypeProtocol::Protocol_MYSQL_TYPE_SET:
                    $this->columnMetaDef[$i] = $this->bodyBuffer->getSubByteBuffer($offset, 2);//$reader->readNumber(2);
                    $offset += 2;
                    break;
                case TableColumnTypeProtocol::Protocol_MYSQL_TYPE_BLOB:
                case TableColumnTypeProtocol::Protocol_MYSQL_TYPE_DOUBLE:
                case TableColumnTypeProtocol::Protocol_MYSQL_TYPE_FLOAT:
                case TableColumnTypeProtocol::Protocol_MYSQL_TYPE_DATETIME2:
                case TableColumnTypeProtocol::Protocol_MYSQL_TYPE_TIMESTAMP2:
                    $this->columnMetaDef[$i] = $this->bodyBuffer->getSubByteBuffer($offset, 1);//$reader->readNumber(1);
                    $offset += 1;
                    break;
                case TableColumnTypeProtocol::Protocol_MYSQL_TYPE_BIT:
                case TableColumnTypeProtocol::Protocol_MYSQL_TYPE_DATE:
                case TableColumnTypeProtocol::Protocol_MYSQL_TYPE_DATETIME:
                case TableColumnTypeProtocol::Protocol_MYSQL_TYPE_TIMESTAMP:
                case TableColumnTypeProtocol::Protocol_MYSQL_TYPE_TIME:
                case TableColumnTypeProtocol::Protocol_MYSQL_TYPE_TINY:
                case TableColumnTypeProtocol::Protocol_MYSQL_TYPE_SHORT:
                case TableColumnTypeProtocol::Protocol_MYSQL_TYPE_INT24:
                case TableColumnTypeProtocol::Protocol_MYSQL_TYPE_LONG:
                case TableColumnTypeProtocol::Protocol_MYSQL_TYPE_LONGLONG:
                default:
                    $this->columnMetaDef[$i] = null;
            }

        }


        $nullBitmapLength = ($this->columnCount + 7) >> 3;
//        $this->nullBitmap=$reader->readString($nullBitmapLength);
        $this->nullBitmap = $this->bodyBuffer->getSubByteBuffer($offset, $nullBitmapLength);//$reader->readByteBuffer($nullBitmapLength);
        $offset += $nullBitmapLength;
    }
}