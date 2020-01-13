<?php


namespace sinri\BinlogReader;


use Exception;
use sinri\ark\core\ArkLogger;
use sinri\BinlogReader\entity\BaseBinlogV4EventEntity;
use sinri\BinlogReader\entity\BinlogV4EventHeaderEntity;
use sinri\BinlogReader\entity\FormatDescriptionEventEntity;
use sinri\BinlogReader\entity\TableMapEventEntity;

class BinlogReader
{
//    const EVENT_TYPE_OFFSET   = 4;
//    const SERVER_ID_OFFSET  =   5;
//    const EVENT_LEN_OFFSET  =   9;
//    const LOG_POS_OFFSET    =   13;
//    const FLAGS_OFFSET        = 17;

    const CHECKSUM_MODE_CRC32="CRC32";
    const CHECKSUM_MODE_NONE="NONE";

    private static $checksumMode='CRC32';
    protected $binlogFile;
    protected $binlogFileHandler;
    protected $binlogVersion;
    /**
     * @var ArkLogger
     */
    protected $logger;
    /**
     * @var FormatDescriptionEventEntity
     */
    protected $formatDescriptionEventEntity;
    /**
     * @var TableMapEventEntity[]
     */
    protected $tableMapDict;

    public function __construct($binlogFile,$binlogVersion='v4')
    {
        $this->binlogFile=$binlogFile;
        $this->binlogVersion=$binlogVersion;
        if(!$this->isBinlogVersionSupported())throw new Exception("Unsupported Binlog Version");

        $this->logger=ArkLogger::makeSilentLogger();
    }

    protected function isBinlogVersionSupported(){
        if($this->binlogVersion==='v4')return true;
        return false;
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

    /**
     * @return FormatDescriptionEventEntity
     */
    public function getFormatDescriptionEventEntity(): FormatDescriptionEventEntity
    {
        return $this->formatDescriptionEventEntity;
    }

    /**
     * @return ArkLogger
     */
    public function getLogger(): ArkLogger
    {
        return $this->logger;
    }

    /**
     * @param ArkLogger $logger
     * @return BinlogReader
     */
    public function setLogger(ArkLogger $logger)
    {
        $this->logger = $logger;
        return $this;
    }

    /**
     * @return int[]
     * @throws Exception
     */
    public function readLecencBuffer(){
        $length=$this->readLenencInt();
        $array=[];
        for($i=0;$i<$length;$i++){
            $array[]=$this->readNumber(1);
        }
        return $array;
    }

    /**
     * @return int|null|false
     * @throws Exception
     */
    public function readLenencInt(){
        $type=$this->readNumber(1);
        if($type<0xfb){
            return $type;
        }
        if($type==0xfb){
            // 0xfb代表NULL，就是mysql插入值往往会是空值，指的就是NULL
            return null;
        }
        if($type==0xfc){
            return $this->readNumber(2);
        }
        if($type==0xfd){
            return $this->readNumber(3);
        }
        if($type==0xfe){
            return $this->readNumber(8);
        }
        if($type==0xff){
            // 0xff在mysql交互协议中一般代表某一个动作错误,如发送一个插入命令失败后会有这种回复出现。
            return false;
        }
        // ERROR now.
        return -1;
    }

    /**
     * mysql日志是小端字节序
     * @param int $length
     * @return int
     * @throws Exception
     */
    public function readNumber(int $length){
        if($length<=0) throw new Exception(__METHOD__.'@'.__LINE__);
//        $bin="";
//        for($i=0;$i<$length;$i++){
//            $c=fgetc($this->binlogFileHandler);
//            $cBin=str_pad(decbin(ord($c)),8,'0',STR_PAD_LEFT);
//            $bin=$cBin.$bin;
//        }
//        return bindec($bin);

        $hex="";
        for($i=0;$i<$length;$i++){
            $c=fgetc($this->binlogFileHandler);
            $cHex=str_pad(dechex(ord($c)),2,'0',STR_PAD_LEFT);
            $hex=$cHex.$hex;
        }
        return hexdec($hex);
    }

    /**
     * @param int $length
     * @return int[]
     * @throws Exception
     */
    public function readByteBuffer(int $length){
        $array=[];
        for($i=0;$i<$length;$i++){
            $array[]=$this->readNumber(1);
        }
        return $array;
    }

    /**
     * @return false|string
     * @throws Exception
     */
    public function readLenencString(){
        $stringLength=$this->readLenencInt();
        if($stringLength==0)return "";
        return $this->readString($stringLength);
    }

    /**
     * @param int $length
     * @return false|string
     * @throws Exception
     */
    public function readString(int $length)
    {
        $this->logger->debug(__METHOD__ . '@' . __LINE__, ['length' => $length]);
        if ($length <= 0) throw new Exception(__METHOD__ . '@' . __LINE__ . ' length is lower,  as ' . $length);
        $text = fread($this->binlogFileHandler, $length);
        for ($i = 0; $i < strlen($text); $i++) {
            if ($text[$i] === "\0") {
                return substr($text, 0, $i);
            }
        }
        return $text;
    }

    public function readStringEndedWithZero()
    {
        $text = "";
        do {
            $char = fread($this->binlogFileHandler, 1);
            if ($char === "\0") {
                break;
            }
            $text .= $char;
        } while (true);
        return $text;
    }

    public function openFile()
    {
        $this->binlogFileHandler = fopen($this->binlogFile, "rb");
        if (!$this->binlogFileHandler) throw new Exception("Cannot open binlog file to read");
        return $this;
    }

    public function closeFile()
    {
        fclose($this->binlogFileHandler);
        return $this;
    }

    /**
     * @throws Exception
     */
    public function parse()
    {
        $this->checkMagicNumber();

        // start event : format_desc
        do {
            $currentFileOffset=ftell($this->binlogFileHandler);
            $this->logger->debug("Now File Offset: ".$currentFileOffset);
            $entity = BaseBinlogV4EventEntity::parseNextEvent($this);

            if($entity===false)break;
            if ($entity->header->typeCode === BinlogV4EventHeaderEntity::TYPE_FORMAT_DESCRIPTION_EVENT) {
                $this->formatDescriptionEventEntity = $entity;
                //$this->logger->debug(__METHOD__ . '@' . __LINE__ . ' Found Format Description Event!' . $this->formatDescriptionEventEntity);
            }elseif($entity->header->typeCode===BinlogV4EventHeaderEntity::TYPE_TABLE_MAP_EVENT){
                $this->tableMapDict[$entity->tableId]=$entity;
            }
            //$this->logger->info(__METHOD__ . '@' . __LINE__ . " Found Type 0x".dechex($entity->header->typeCode),['type'=>$entity->header->getTypeName()]);
            $this->logger->info(__METHOD__ . '@' . __LINE__ .' Current Event ↓'. PHP_EOL.$entity);
            $this->logger->debug("Next Position: ".$entity->header->nextPosition);
        }while($entity!==false);

        return $this;
    }

    /**
     * @throws Exception
     */
    private function checkMagicNumber(){
        // magic number
        $magic_number_chars = fread($this->binlogFileHandler, 4);
        //echo $magic_number_chars . PHP_EOL;

        if ($magic_number_chars !== "\xfe\x62\x69\x6e") {
            throw new Exception("Invalid Binlog Magic Number");
        }
    }

    /**
     * @param $nextPosition
     * @return int|null
     * @throws Exception
     */
    public final function readCrc32Tail($nextPosition){
        $here=ftell($this->binlogFileHandler);
        if($nextPosition-$here==BaseBinlogV4EventEntity::checksumByteCount()){
            return $this->readNumber(BaseBinlogV4EventEntity::checksumByteCount());
        }elseif($nextPosition!=$here){
            $this->logger->warning("Checksum Bytes Invalid",['here'=>$here,'next'=>$nextPosition]);
        }
        return null;
    }

    /**
     * @param int $tableId
     * @return TableMapEventEntity
     */
    public function getTableMapItemById($tableId){
        return $this->tableMapDict[$tableId];
    }

    /**
     * @param int $nextPosition
     * @return bool True for Reached Tail
     * @throws Exception
     */
    public function checkIfReachedTail($nextPosition){
        $here = ftell($this->binlogFileHandler);
        $this->logger->debug(__METHOD__, ['here' => $here, 'nextPosition' => $nextPosition, 'checksum_bytes' => BaseBinlogV4EventEntity::checksumByteCount()]);
        if ($here > $nextPosition) throw new Exception("Over Drive!");
        return ($nextPosition==$here+BaseBinlogV4EventEntity::checksumByteCount());
    }

    /**
     * @return string
     */
    public function getBinlogVersion(): string
    {
        return $this->binlogVersion;
    }
}