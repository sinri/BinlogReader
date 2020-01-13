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
        $reader->getLogger()->debug(__METHOD__ . '@' . __LINE__, ['method' => $this->method, 'version' => $this->version]);

//        $this->debugShowBody($reader);
//        return;

        /*
         * V1 WRITE
         * 000000	| 4d(M) 00( ) 00( ) 00( ) 00( ) 00( )|01() 00( )|04()|ff(�)|
         * 000010	|[f0(�):49(I) 81(�) a5(�) 00( )|99(�) a5(�) 4c(L) 64(d) b2(�)|
         * 000020	| 99(�) a5(�)?4c(L) 64(d) c0(�)|99(�) a5(�) 4c(L) 67(g) 40(@)]
         * 000030	| 17()?36(6) 48(H) 2e(.)
         */

        /*
         * 000000	| 52(R) 00( ) 00( ) 00( ) 00( ) 00( )|01() 00( )|03()|ff(�)|
         * 000010	|[f8(�):36(6)→00( ) 63(c) 6f(o) 6d(m) 6d(m) 61(a) 6e(n) 64(d) // 8
         * 000020	| 73(s) 2e(.) 65(e) 72(r) 70(p) 73(s) 79(y) 6e(n) 63(c) 74(t)
         * 000030	| 61(a) 6f(o) 62(b) 61(a) 6f(o) 2e(.) 54(T) 61(a) 6f(o) 62(b)
         * 000040	| 61(a) 6f(o) 4f(O) 72(r) 64(d) 65(e) 72(r) 54(T) 72(r) 61(a)
         * 000050	| 6e(n) 73(s) 66(f) 65(e) 72(r) 57(W) 69(i) 74(t) 68(h) 47(G) // 4*10
         * 000060	| 72(r) 6f(o) 75(u) 70(p) 46(F) 32(2) 32(2)|21(!)→00( ) 31(1) // 7
         * 000070	| 30(0) 36(6) 31(1) 34(4) 30(0) 35(5) 31(1) 31(1) 34(4) 35(5)
         * 000080	| 65(e) 31(1) 32(2) 36(6) 33(3) 32(2) 64(d) 37(7) 65(e) 39(9)
         * 000090	| 37(7) 62(b) 39(9) 2e(.) 39(9) 38(8) 38(8) 36(6) 30(0) 37(7)
         * 000100	| 34(4) 39(9) 9d(�) 11() 61(a) 61(a) 99(�) 84(�) d7(�) 41(A)
         * 000110	| cd(�) 48(H) 78(x) f6(�)
         */

//        2020-01-13 16:03:01 [debug] sinri\BinlogReader\entity\RowsEventEntity::readFromBinlogStream@87 |{"method":"UPDATE","version":1}
//        Debug Show Body ↓
//        000000	| 55(U) 00( ) 00( ) 00( ) 00( ) 00( )|01() 00( )|02()|ff(�)|
//        000010	|[ff(�):fc(�) 7c(|) 4f(O) d3(�) 77(w) 6f(o) 01() 00( )|00( )
//        000020	| 01() 6d(m)|fc(�) 30(0) 8d(�) d3(�) 77(w) 6f(o) 01() 00( )|
//        000030	| 00( ) 01() 6d(m)|ff(�) 21(!) 6f(o) fc(�)

//        2020-01-13 16:32:37 [debug] sinri\BinlogReader\entity\RowsEventEntity::readFromBinlogStream@87 |{"method":"WRITE","version":1}
//        Debug Show Body ↓
//        000000	| 53(S) 00( ) 00( ) 00( ) 00( ) 00( )|01() 00( )|06()|ff(�)|
//        000010	|[c0(�):20( )→30(0) 63(c) 36(6) 64(d) 34(4) 61(a) 36(6) 63(c) //  8
//        000020	| 64(d) 66(f) 61(a) 38(8) 31(1) 66(f) 35(5) 37(7) 32(2) 38(8) // 10
//        000030	| 30(0) 33(3) 39(9) 61(a) 35(5) 61(a) 30(0) 66(f) 35(5) 61(a) // 10
//        000040	| 36(6) 33(3) 33(3) 39(9)|69(i) 63(c) 12() 5e(^)|00( ) 00( ) //  4
//        000050	| 00( )|00( ) 00( ) 00( )|00( )|06() 00( ) 00( ) 00( )→61(a)
//        000060	| 3a(:) 30(0) 3a(:) 7b({) 7d(})]7d(}) b9(�) 50(P) 02()

        $reader->getLogger()->debug(__METHOD__ . '@' . __LINE__, ['next position' => $this->header->nextPosition]);

        $post_header_len = $reader->getFormatDescriptionEventEntity()->postHeaderLengthForAllEventTypes[$this->header->typeCode];
        if ($post_header_len == 6) {
            $this->tableId = $reader->readNumber(4);
        } else {
            $this->tableId = $reader->readNumber(6);
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
            $row = [];

            $this->handleRow($reader, 1, $row);
            if ($this->method == self::TYPE_UPDATE && ($this->version == self::VERSION_1 || $this->version == self::VERSION_2)) {
                $this->handleRow($reader, 2, $row);
            }

            $this->rows[] = $row;

            $reader->getLogger()->debug(__METHOD__ . '@' . __LINE__);
        }
    }

    /**
     * @param BinlogReader $reader
     * @param int $imageId 1|2
     * @param array $row
     * @throws Exception
     */
    private function handleRow($reader,$imageId,&$row)
    {
        $reader->getLogger()->debug(__METHOD__ . '@' . __LINE__ . ' ---------');

        if ($imageId == 1) {
            $parsedColumnsForImage = $this->parsedColumns1;
        } else {
            $parsedColumnsForImage = $this->parsedColumns2;
        }

        $row[$imageId - 1] = [];

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
            $reader->getLogger()->debug(__METHOD__ . '@' . __LINE__ . ' ----');
            $parsedColumn = $parsedColumnsForImage[$i];
            $reader->getLogger()->debug(__METHOD__ . '@' . __LINE__, ['parsed column' => $parsedColumn]);
            if ($row[$imageId - 1][$i]['is_null'] === true) {
                $reader->getLogger()->debug(__METHOD__ . '@' . __LINE__, ["Column $i" => 'is null']);
                continue;
            }
            // read value by column_type_def of parsed column
            $row[$imageId - 1][$i]['value'] = TableColumnTypeProtocol::readValueByType($reader, $parsedColumn['column_type_def'], $parsedColumn['column_meta']);
            $reader->getLogger()->debug(__METHOD__ . '@' . __LINE__, ['value' => $row[$imageId - 1][$i]['value'], 'type' => $parsedColumn['column_type_def'], 'meta' => $parsedColumn['column_meta']]);
        }

        for ($i = 0; $i < count($row[$imageId-1]); $i++) {
            $reader->getLogger()->debug(__METHOD__ . '@' . __LINE__, ["Column $i" => $row[$imageId - 1][$i]]);
        }
    }

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
//        return json_encode($this);
    }

    /**
     * @param BinlogReader $reader
     * @return TableMapEventEntity
     */
    private function getTableFromTableMap($reader){
       return $reader->getTableMapItemById($this->tableId);
    }
}