<?php


namespace sinri\BinlogReader\entity;


use sinri\BinlogReader\BinlogReader;
use sinri\BinlogReader\BRKit;

class TableMapEventEntity extends BaseBinlogV4EventEntity
{

    public $tableId;
    public $flags;

    public $schemaNameLength;
    public $schemaName;
    public $tableNameLength;
    public $tableName;
    public $columnCount;
    /**
     * @var array [len=column_count] array of column definitions, one byte per field type
     * @see https://dev.mysql.com/doc/internals/en/com-query-response.html#packet-Protocol::ColumnType
     */
    public $columnTypeDef;
    /**
     * @var string array of metainfo per column, length is the overall length of the metainfo-array in bytes, the length of each metainfo field is dependent on the columns field type
     */
    public $columnMetaDef;
    public $nullBitmap;

    /**
     * @inheritDoc
     */
    public function readFromBinlogStream($reader)
    {
//        $this->debugShowBody($reader);
//        return;

        /*
         * 000000	| 4d 00 00 00 00 00|01 00|05→72
         * 000010	| 6f 6d 65 6f 00|20→69 6e 76 65
         * 000020	| 6e 74 6f 72 79 5f 72 65 73 65
         * 000030	| 72 76 65 5f 69 6e 63 72 65 6d
         * 000040	| 65 6e 74 5f 73 74 65 70 00|04|
         * 000050	| 03 12 12 12|03|00 00 00|0e|a7
         * 000060	| 35 93 86
         */

        $post_header_len=$reader->getFormatDescriptionEventEntity()->postHeaderLengthForAllEventTypes[$this->header->typeCode];
        //var_dump($post_header_len);
        if($post_header_len==6){
            $this->tableId=$reader->readNumber(4);
        }else{
            $this->tableId=$reader->readNumber(6);
        }
        $this->flags=$reader->readNumber(2);

        $this->schemaNameLength=$reader->readNumber(1);
        $this->schemaName=$reader->readString($this->schemaNameLength);
        $reader->readNumber(1);// 0x00
        $this->tableNameLength=$reader->readNumber(1);
        $this->tableName=$reader->readString($this->tableNameLength);
        $reader->readNumber(1);// 0x00

        $this->columnCount=$reader->readLenencInt();
        for($i=0;$i<$this->columnCount;$i++) {
            $this->columnTypeDef[$i] = $reader->readNumber(1);
        }

        //$this->columnMetaDef=$reader->readLenencString();
        $columnMetaDefBytes=$reader->readNumber(1);
//        for($i=0;$i<$columnMetaDefBytes;$i++){
//            $this->columnMetaDef[]=$reader->readNumber(1);
//        }
        for($i=0;$i<$this->columnCount;$i++){
            // TODO the length definition @see https://dev.mysql.com/doc/internals/en/table-map-event.html
            // not fulfilled yet!
            switch ($this->columnTypeDef[$i]){
                case TableColumnTypeProtocol::Protocol_MYSQL_TYPE_STRING:
                case TableColumnTypeProtocol::Protocol_MYSQL_TYPE_VAR_STRING:
                case TableColumnTypeProtocol::Protocol_MYSQL_TYPE_VARCHAR:
                case TableColumnTypeProtocol::Protocol_MYSQL_TYPE_DECIMAL:
                case TableColumnTypeProtocol::Protocol_MYSQL_TYPE_NEWDECIMAL:
                case TableColumnTypeProtocol::Protocol_MYSQL_TYPE_ENUM:
                case TableColumnTypeProtocol::Protocol_MYSQL_TYPE_SET:
                    $this->columnMetaDef[$i]=$reader->readNumber(2);
                    break;
                case TableColumnTypeProtocol::Protocol_MYSQL_TYPE_BLOB:
                case TableColumnTypeProtocol::Protocol_MYSQL_TYPE_DOUBLE:
                case TableColumnTypeProtocol::Protocol_MYSQL_TYPE_FLOAT:
                case TableColumnTypeProtocol::Protocol_MYSQL_TYPE_DATETIME2:
                case TableColumnTypeProtocol::Protocol_MYSQL_TYPE_TIMESTAMP2:
                    $this->columnMetaDef[$i]=$reader->readNumber(1);
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
                    $this->columnMetaDef[$i]=null;
            }

        }


        $nullBitmapLength=floor(($this->columnCount + 7) / 8);
//        $this->nullBitmap=$reader->readString($nullBitmapLength);
        $this->nullBitmap=$reader->readByteBuffer($nullBitmapLength);

        //var_dump($this);
    }

    public function getHumanReadableDescription()
    {
        $s= "Table ID: ".$this->tableId." ".$this->schemaName.' . '.$this->tableName.PHP_EOL
            ."Flags: ".$this->flags." Column Count: ".$this->columnCount.PHP_EOL;
        $s.="Table Column Types: ".PHP_EOL;
        for($i=0;$i<$this->columnCount;$i++){
            $s.="[$i] ".BRKit::hexOneNumber($this->columnTypeDef[$i]).PHP_EOL;
        }
        $s.="Column Meta Def: ".BRKit::hexInlineNumbers($this->columnMetaDef).PHP_EOL;
        $s.="Null Bit Map: ".BRKit::binInlineNumbers($this->nullBitmap);
        return $s;
    }
}