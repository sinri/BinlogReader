<?php


namespace sinri\BinlogReader\entity;


use Exception;
use sinri\ark\core\ArkHelper;
use sinri\BinlogReader\BREnv;

class RowsEventEntity extends BaseEventEntity
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
    public function getHumanReadableDescription()
    {
        $s = "METHOD: " . $this->method . ' VERSION: ' . $this->version . PHP_EOL
            . 'Table ID: ' . $this->tableId . ' Flag: ' . $this->flags . PHP_EOL
            . ($this->version === self::VERSION_2 ? (json_encode($this->extraData) . PHP_EOL) : '')
            . 'Column Count: ' . $this->columnNumber . PHP_EOL;
        for ($rowIndex = 0; $rowIndex < count($this->rows); $rowIndex++) {
            $s .= "Image for Row " . $rowIndex . PHP_EOL;
            //var_dump($this->rows[$rowIndex][0]);
            for ($i = 0; $i < count($this->rows[$rowIndex][0]); $i++) {
                $s .= json_encode($this->rows[$rowIndex][0][$i]) . PHP_EOL;
            }
            if (count($this->rows[$rowIndex]) > 1) {
                $s .= 'Rows matched above conditions were updated to this:' . PHP_EOL;
                for ($i = 0; $i < count($this->rows[$rowIndex][1]); $i++) {
                    $s .= json_encode($this->rows[$rowIndex][1][$i]) . PHP_EOL;
                }
            }
        }
        return $s;
    }

    /**
     * @inheritDoc
     */
    public function parseBodyBuffer()
    {
        BREnv::getLogger()->debug(__METHOD__ . '@' . __LINE__, ['method' => $this->method, 'version' => $this->version]);

        BREnv::getLogger()->debug(__METHOD__ . '@' . __LINE__, ['next position' => $this->header->nextPosition]);

        $post_header_len = self::$currentFormatDescriptionEventEntity->postHeaderLengthForAllEventTypes[$this->header->typeCode];

        $offset = 0;

        if ($post_header_len == 6) {
            $this->tableId = $this->bodyBuffer->readNumberWithSomeBytesLE($offset, 4);//$reader->readNumber(4);
            $offset += 4;
        } else {
            $this->tableId = $this->bodyBuffer->readNumberWithSomeBytesLE($offset, 6);//$reader->readNumber(6);
            $offset += 6;
        }

        $this->flags = $this->bodyBuffer->readNumberWithSomeBytesLE($offset, 2);//$reader->readNumber(2);
        $offset += 2;

        if ($this->version == self::VERSION_2) {
            $this->extraDataLength = $this->bodyBuffer->readNumberWithSomeBytesLE($offset, 2);//$reader->readNumber(2);
            $offset += 2;
            $this->extraData = $this->bodyBuffer->getSubByteBuffer($offset, $this->extraDataLength - 2);//$reader->readByteBuffer($this->extraDataLength-2);
            $offset += $this->extraDataLength;
            // for the details about extra data, @see https://dev.mysql.com/doc/internals/en/rows-event.html#write-rows-eventv1
        }

        // body
        $this->columnNumber = $this->bodyBuffer->readLenencInt($offset, $tempLength);//$reader->readLenencInt();
        $offset += $tempLength;
        $tempLength = ($this->columnNumber + 7) >> 3;
        $this->columnsPresentBitmap1 = $this->bodyBuffer->getSubByteBuffer($offset, $tempLength);//$reader->readByteBuffer(floor(($this->columnNumber+7)/8));
        $offset += $tempLength;

        $this->columnsPresentBitmap1->checkBitmap($this->columnNumber, function ($bit, $order) {
            if ($bit == 1) {
                $this->parsedColumns1[$order] = [
                    'column_def_index' => $order,
                    'column_type_def' => $this->getTableFromTableMap()->columnTypeDef[$order],
                    'column_type_name' => TableColumnTypeProtocol::getTypeName($this->getTableFromTableMap()->columnTypeDef[$order]),
                    'column_meta' => $this->getTableFromTableMap()->columnMetaDef[$order],
                ];
            }
        });

//        BRKit::checkBitmap($this->columnsPresentBitmap1->getBytesAsArray(),$this->columnNumber,function ($bit,$order) {
//            if($bit==1){
//                $this->parsedColumns1[$order]=[
//                    'column_def_index'=>$order,
//                    'column_type_def'=>$this->getTableFromTableMap()->columnTypeDef[$order],
//                    'column_type_name'=>TableColumnTypeProtocol::getTypeName($this->getTableFromTableMap()->columnTypeDef[$order]),
//                    'column_meta'=>$this->getTableFromTableMap()->columnMetaDef[$order],
//                ];
//            }
//        });

        if ($this->method == self::TYPE_UPDATE && ($this->version == self::VERSION_1 || $this->version == self::VERSION_2)) {
            $tempLength = ($this->columnNumber + 7) >> 3;
            $this->columnsPresentBitmap2 = $this->bodyBuffer->getSubByteBuffer($offset, $tempLength);//$reader->readByteBuffer(floor(($this->columnNumber+7)/8));
            $offset += $tempLength;

            $this->columnsPresentBitmap2->checkBitmap($this->columnNumber, function ($bit, $order) {
                if ($bit == 1) {
                    $this->parsedColumns2[] = [
                        'column_def_index' => $order,
                        'column_type_def' => $this->getTableFromTableMap()->columnTypeDef[$order],
                        'column_type_name' => TableColumnTypeProtocol::getTypeName($this->getTableFromTableMap()->columnTypeDef[$order]),
                        'column_meta' => $this->getTableFromTableMap()->columnMetaDef[$order],
                    ];
                }
            });

//            BRKit::checkBitmap($this->columnsPresentBitmap2->getBytesAsArray(),$this->columnNumber,function ($bit,$order) {
//                if($bit==1){
//                    $this->parsedColumns2[]=[
//                        'column_def_index'=>$order,
//                        'column_type_def'=>$this->getTableFromTableMap()->columnTypeDef[$order],
//                        'column_type_name'=>TableColumnTypeProtocol::getTypeName($this->getTableFromTableMap()->columnTypeDef[$order]),
//                        'column_meta'=>$this->getTableFromTableMap()->columnMetaDef[$order],
//                    ];
//                }
//            });
        }

        BREnv::getLogger()->debug(__METHOD__ . '@' . __LINE__, [
                'column_number' => $this->columnNumber,
                'bitmap1' => $this->columnsPresentBitmap1,
                'bitmap2' => $this->columnsPresentBitmap2,
            ]
        );

        // rows
        // @see https://dev.mysql.com/doc/internals/en/event-data-for-specific-event-types.html [WRITE_ROWS_EVENT]
        while ($offset < $this->bodyBuffer->getSize()) {
            //while(!$reader->checkIfReachedTail($this->header->nextPosition)) {
            $row = [];

            $this->handleRow($offset, 1, $row);
            if ($this->method == self::TYPE_UPDATE && ($this->version == self::VERSION_1 || $this->version == self::VERSION_2)) {
                $this->handleRow($offset, 2, $row);
            }

            $this->rows[] = $row;

            BREnv::getLogger()->debug(__METHOD__ . '@' . __LINE__);
        }
    }

    /**
     * @param int $offset
     * @param int $imageId 1|2
     * @param array $row
     * @throws Exception
     */
    private function handleRow(&$offset, $imageId, &$row)
    {
        BREnv::getLogger()->debug(__METHOD__ . '@' . __LINE__ . ' ---------');

        if ($imageId == 1) {
            $parsedColumnsForImage = $this->parsedColumns1;
        } else {
            $parsedColumnsForImage = $this->parsedColumns2;
        }

        $row[$imageId - 1] = [];

        $nullValueBitmapLength = ((count($parsedColumnsForImage) + 7) >> 3);
        $nullValueBitmap = $this->bodyBuffer->getSubByteBuffer($offset, $nullValueBitmapLength);//$reader->readByteBuffer($nullValueBitmapLength);
        $offset += $nullValueBitmapLength;

        BREnv::getLogger()->debug(__METHOD__ . '@' . __LINE__, ['null bit map' => ['length' => $nullValueBitmapLength, 'map' => $nullValueBitmap]]);

        $nullValueBitmap->checkBitmap(count($parsedColumnsForImage), function ($bit, $order) use ($imageId, $parsedColumnsForImage, &$row) {
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
            $row[$imageId - 1][] = $column;
        });

//        BRKit::checkBitmap($nullValueBitmap->getBytesAsArray(), count($parsedColumnsForImage), function ($bit, $order) use ($imageId, $parsedColumnsForImage, &$row) {
//            $parsedColumn = $parsedColumnsForImage[$order];
//            //$reader->getLogger()->debug(__METHOD__.'@'.__LINE__,['parsed column'=>$parsedColumn]);
//            //$reader->getLogger()->debug(__METHOD__.'@'.__LINE__,['bit'=>$bit,'order'=>$order]);
//            $column = [
//                'used_column_index' => $order,
//                'column_def_index' => $parsedColumn['column_def_index'],
//                'is_null' => false,
//                'value' => null
//            ];
//            if ($bit == 1) {
//                $column['is_null'] = true;
//            }
//            $row[$imageId-1][] = $column;
//        });

        //$reader->getLogger()->debug(__METHOD__.'@'.__LINE__,['row phrase 1'=>$row]);

        for ($i = 0; $i < count($row[$imageId - 1]); $i++) {
            BREnv::getLogger()->debug(__METHOD__ . '@' . __LINE__ . ' ----');
            $parsedColumn = $parsedColumnsForImage[$i];
            BREnv::getLogger()->debug(__METHOD__ . '@' . __LINE__, ['parsed column' => $parsedColumn]);
            if ($row[$imageId - 1][$i]['is_null'] === true) {
                BREnv::getLogger()->debug(__METHOD__ . '@' . __LINE__, ["Column $i" => 'is null']);
                continue;
            }
            // read value by column_type_def of parsed column
            $row[$imageId - 1][$i]['value'] = $this->bodyBuffer->readValueByType($parsedColumn['column_type_def'], $parsedColumn['column_meta'], $offset, $tempLength);
            $offset += $tempLength;
            //$row[$imageId - 1][$i]['value'] = TableColumnTypeProtocol::readValueByType($reader, $parsedColumn['column_type_def'], $parsedColumn['column_meta']);
            BREnv::getLogger()->debug(__METHOD__ . '@' . __LINE__, ['value' => $row[$imageId - 1][$i]['value'], 'type' => $parsedColumn['column_type_def'], 'meta' => $parsedColumn['column_meta']]);
        }

        for ($i = 0; $i < count($row[$imageId - 1]); $i++) {
            BREnv::getLogger()->debug(__METHOD__ . '@' . __LINE__, ["Column $i" => $row[$imageId - 1][$i]]);
        }
    }

    /**
     * @return TableMapEventEntity
     */
    private function getTableFromTableMap()
    {
        return ArkHelper::readTarget(BaseEventEntity::$tableMap, [$this->tableId]);
    }
}