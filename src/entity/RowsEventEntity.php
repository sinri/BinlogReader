<?php


namespace sinri\BinlogReader\entity;


use Exception;
use sinri\BinlogReader\BinlogReader;
use sinri\BinlogReader\BRKit;

class RowsEventEntity extends BaseBinlogV4EventEntity
{
    /*
     * Version 0 written from MySQL 5.1.0 to 5.1.15
     * UPDATE_ROWS_EVENTv0
     * WRITE_ROWS_EVENTv0
     * DELETE_ROWS_EVENTv0
     *
     * Version 1 written from MySQL 5.1.15 to 5.6.x
     * UPDATE_ROWS_EVENTv1
     * WRITE_ROWS_EVENTv1
     * DELETE_ROWS_EVENTv1
     * added the after-image for the UPDATE_ROWS_EVENT
     *
     * Version 2 written from MySQL 5.6.x
     * UPDATE_ROWS_EVENTv2
     * WRITE_ROWS_EVENTv2
     * DELETE_ROWS_EVENTv2
     * added the extra-data fields
     */

    const VERSION_0=0;
    const VERSION_1=1;
    const VERSION_2=2;

    const TYPE_UPDATE="UPDATE";
    const TYPE_WRITE="WRITE";
    const TYPE_DELETE="DELETE";

    /**
     * @var int this must be set first, before `readFromBinlogStream`
     */
    public $version;
    /**
     * @var string this must be set first, before `readFromBinlogStream`
     */
    public $method;
    /**
     * @var int
     * If the table id is 0x00ffffff it is a dummy event
     * that should have the end of statement flag set
     * that declares that all table maps can be freed.
     * Otherwise it refers to a table defined by TABLE_MAP_EVENT.
     */
    public $tableId;
    /**
     * @var int
     * 0x0001 end of statement
     * 0x0002 no foreign key checks
     * 0x0004 no unique key checks
     * 0x0008 row has a columns
     */
    public $flags;
    public $extraDataLength;
    public $extraData;

    public $columnNumber;

    public $columnsPresentBitmap1;
    public $columnsPresentBitmap2;
    /**
     * @var array
     */
    public $parsedColumns1;
    /**
     * @var array
     */
    public $parsedColumns2;

    public $rows;

    /**
     * @inheritDoc
     */
    public function readFromBinlogStream($reader)
    {
//        $this->debugShowBody($reader);
//        return;

        /**
         * V1 WRITE
         * 000000	| 4d(M) 00( ) 00( ) 00( ) 00( ) 00( )|01() 00( )|04()|ff(�)|
         * 000010	|[f0(�):49(I) 81(�) a5(�) 00( )|99(�) a5(�) 4c(L) 64(d) b2(�)|
         * 000020	| 99(�) a5(�)?4c(L) 64(d) c0(�)|99(�) a5(�) 4c(L) 67(g) 40(@)]
         * 000030	| 17()?36(6) 48(H) 2e(.)
         */

        $reader->getLogger()->debug(__METHOD__.'@'.__LINE__,['next position'=>$this->header->nextPosition]);

        $post_header_len=$reader->getFormatDescriptionEventEntity()->postHeaderLengthForAllEventTypes[$this->header->typeCode];
        if($post_header_len==6){
            $this->tableId=$reader->readNumber(4);
        }else{
            $this->tableId=$reader->readNumber(6);
        }

        $this->flags=$reader->readNumber(2);

        if ($this->version==self::VERSION_2){
            $this->extraDataLength=$reader->readNumber(2);
            $this->extraData=$reader->readByteBuffer($this->extraDataLength-2);
            // for the details about extra data, @see https://dev.mysql.com/doc/internals/en/rows-event.html#write-rows-eventv1
        }

        // body
        $this->columnNumber=$reader->readLenencInt();
        $this->columnsPresentBitmap1=$reader->readByteBuffer(floor(($this->columnNumber+7)/8));

        BRKit::checkBitmap($this->columnsPresentBitmap1,$this->columnNumber,function ($bit,$order) use ($reader) {
            if($bit==1){
                $this->parsedColumns1[$order]=[
                    'column_def_index'=>$order,
                    'column_type_def'=>$this->getTableFromTableMap($reader)->columnTypeDef[$order],
                    'column_type_name'=>TableColumnTypeProtocol::getTypeName($this->getTableFromTableMap($reader)->columnTypeDef[$order]),
                    'column_meta'=>$this->getTableFromTableMap($reader)->columnMetaDef[$order],
                ];
            }
        });

        if($this->method==self::TYPE_UPDATE && ($this->version==self::VERSION_1 || $this->version==self::VERSION_2)){
            $this->columnsPresentBitmap2=$reader->readByteBuffer(floor(($this->columnNumber+7)/8));

            BRKit::checkBitmap($this->columnsPresentBitmap2,$this->columnNumber,function ($bit,$order) use ($reader) {
                if($bit==1){
                    $this->parsedColumns2[]=[
                        'column_def_index'=>$order,
                        'column_type_def'=>$this->getTableFromTableMap($reader)->columnTypeDef[$order],
                        'column_type_name'=>TableColumnTypeProtocol::getTypeName($this->getTableFromTableMap($reader)->columnTypeDef[$order]),
                        'column_meta'=>$this->getTableFromTableMap($reader)->columnMetaDef[$order],
                    ];
                }
            });
        }

        $reader->getLogger()->debug(__METHOD__.'@'.__LINE__,[
            'column_number'=>$this->columnNumber,
            'bitmap1'=>$this->columnsPresentBitmap1,
            'bitmap2'=>$this->columnsPresentBitmap2,
            ]
        );

        // rows
        // @see https://dev.mysql.com/doc/internals/en/event-data-for-specific-event-types.html [WRITE_ROWS_EVENT]
        while(!$reader->checkIfReachedTail($this->header->nextPosition)) {
            $row=[
                0=>[],
                1=>[],
            ];

            $this->handleRow($reader,1,$row);
            if($this->method==self::TYPE_UPDATE && ($this->version==self::VERSION_1||$this->version==self::VERSION_2)) {
                $this->handleRow($reader,2,$row);
            }

/*
                // 'row-image-1'
            {

                $nullValueBitmapLength = floor((count($this->parsedColumns1) + 7) / 8);
                $nullValueBitmap = $reader->readByteBuffer($nullValueBitmapLength);

                $reader->getLogger()->debug(__METHOD__ . '@' . __LINE__, ['null bit map' => ['length' => $nullValueBitmapLength, 'map' => $nullValueBitmap]]);

                BRKit::checkBitmap($nullValueBitmap, count($this->parsedColumns1), function ($bit, $order) use ($reader, &$row) {
                    $parsedColumn = $this->parsedColumns1[$order];
                    //$reader->getLogger()->debug(__METHOD__.'@'.__LINE__,['parsed column'=>$parsedColumn]);
                    //$reader->getLogger()->debug(__METHOD__.'@'.__LINE__,['bit'=>$bit,'order'=>$order]);
                    $column = [
                        'used_column_index' => $order,
                        'column_def_index' => $parsedColumn['column_def_index'],
                        'is_null' => false,
                        'value' => null
                    ];
                    if ($bit == 1) {
                        $column['is_null'] = true;
                    }
                    $row['row-image-1'][] = $column;
                });

                //$reader->getLogger()->debug(__METHOD__.'@'.__LINE__,['row phrase 1'=>$row]);

                for ($i = 0; $i < count($row['row-image-1']); $i++) {
                    if ($row['row-image-1'][$i]['is_null'] === true) continue;
                    $parsedColumn = $this->parsedColumns1[$i];
                    $reader->getLogger()->debug(__METHOD__ . '@' . __LINE__, ['parsed column' => $parsedColumn]);
                    // read value by column_type_def of parsed column
                    $row['row-image-1'][$i]['value'] = TableColumnTypeProtocol::readValueByType($reader, $parsedColumn['column_type_def'], $parsedColumn['column_meta']);
                }

                for ($i = 0; $i < count($row['row-image-1']); $i++) {
                    $reader->getLogger()->debug(__METHOD__ . '@' . __LINE__, [$i => $row['row-image-1'][$i]]);
                }
            }
            // 'row-image-2'

            if($this->method==self::TYPE_UPDATE && ($this->version==self::VERSION_1||$this->version==self::VERSION_2)){
                $nullValueBitmapLength = floor((count($this->parsedColumns2) + 7) / 8);
                $nullValueBitmap = $reader->readByteBuffer($nullValueBitmapLength);

                $reader->getLogger()->debug(__METHOD__ . '@' . __LINE__, ['null bit map' => ['length' => $nullValueBitmapLength, 'map' => $nullValueBitmap]]);

                BRKit::checkBitmap($nullValueBitmap, count($this->parsedColumns1), function ($bit, $order) use ($reader, &$row) {
                    $parsedColumn = $this->parsedColumns1[$order];
                    //$reader->getLogger()->debug(__METHOD__.'@'.__LINE__,['parsed column'=>$parsedColumn]);
                    //$reader->getLogger()->debug(__METHOD__.'@'.__LINE__,['bit'=>$bit,'order'=>$order]);
                    $column = [
                        'used_column_index' => $order,
                        'column_def_index' => $parsedColumn['column_def_index'],
                        'is_null' => false,
                        'value' => null
                    ];
                    if ($bit == 1) {
                        $column['is_null'] = true;
                    }
                    $row['row-image-1'][] = $column;
                });

                //$reader->getLogger()->debug(__METHOD__.'@'.__LINE__,['row phrase 1'=>$row]);

                for ($i = 0; $i < count($row['row-image-1']); $i++) {
                    if ($row['row-image-1'][$i]['is_null'] === true) continue;
                    $parsedColumn = $this->parsedColumns1[$i];
                    $reader->getLogger()->debug(__METHOD__ . '@' . __LINE__, ['parsed column' => $parsedColumn]);
                    // read value by column_type_def of parsed column
                    $row['row-image-1'][$i]['value'] = TableColumnTypeProtocol::readValueByType($reader, $parsedColumn['column_type_def'], $parsedColumn['column_meta']);
                }

                for ($i = 0; $i < count($row['row-image-1']); $i++) {
                    $reader->getLogger()->debug(__METHOD__ . '@' . __LINE__, [$i => $row['row-image-1'][$i]]);
                }
            }
*/
            $this->rows[]=$row;
        }
    }

    /**
     * @param BinlogReader $reader
     * @param int $imageId 1|2
     * @param array $row
     * @throws Exception
     */
    private function handleRow($reader,$imageId,&$row){
        if($imageId==1){
            $parsedColumnsForImage=$this->parsedColumns1;
        }else{
            $parsedColumnsForImage=$this->parsedColumns2;
        }

        $nullValueBitmapLength = floor((count($parsedColumnsForImage) + 7) / 8);
        $nullValueBitmap = $reader->readByteBuffer($nullValueBitmapLength);

        $reader->getLogger()->debug(__METHOD__ . '@' . __LINE__, ['null bit map' => ['length' => $nullValueBitmapLength, 'map' => $nullValueBitmap]]);

        BRKit::checkBitmap($nullValueBitmap, count($parsedColumnsForImage), function ($bit, $order) use ($imageId, $parsedColumnsForImage, $reader, &$row) {
            $parsedColumn = $parsedColumnsForImage[$order];
            //$reader->getLogger()->debug(__METHOD__.'@'.__LINE__,['parsed column'=>$parsedColumn]);
            //$reader->getLogger()->debug(__METHOD__.'@'.__LINE__,['bit'=>$bit,'order'=>$order]);
            $column = [
                'used_column_index' => $order,
                'column_def_index' => $parsedColumn['column_def_index'],
                'is_null' => false,
                'value' => null
            ];
            if ($bit == 1) {
                $column['is_null'] = true;
            }
            $row[$imageId-1][] = $column;
        });

        //$reader->getLogger()->debug(__METHOD__.'@'.__LINE__,['row phrase 1'=>$row]);

        for ($i = 0; $i < count($row[$imageId-1]); $i++) {
            if ($row[$imageId-1][$i]['is_null'] === true) continue;
            $parsedColumn = $parsedColumnsForImage[$i];
            $reader->getLogger()->debug(__METHOD__ . '@' . __LINE__, ['parsed column' => $parsedColumn]);
            // read value by column_type_def of parsed column
            $row[$imageId-1][$i]['value'] = TableColumnTypeProtocol::readValueByType($reader, $parsedColumn['column_type_def'], $parsedColumn['column_meta']);
        }

        for ($i = 0; $i < count($row[$imageId-1]); $i++) {
            $reader->getLogger()->debug(__METHOD__ . '@' . __LINE__, [$i => $row[$imageId-1][$i]]);
        }
    }

    public function getHumanReadableDescription()
    {
        return json_encode($this);
    }

    /**
     * @param BinlogReader $reader
     * @return TableMapEventEntity
     */
    private function getTableFromTableMap($reader){
       return $reader->getTableMapItemById($this->tableId);
    }
}