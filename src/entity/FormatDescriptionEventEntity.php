<?php


namespace sinri\BinlogReader\entity;


use Exception;
use sinri\BinlogReader\BinlogReader;
use sinri\BinlogReader\BRKit;

class FormatDescriptionEventEntity extends BaseBinlogV4EventEntity
{
    public $binlogVersion;
    public $serverVersion;
    public $createTimestamp;
    public $headerLength;
    /**
     * @var int[]
     */
    public $postHeaderLengthForAllEventTypes;

    public $theLastUnknownByte;

    public function __construct($header)
    {
        parent::__construct($header);
        $this->postHeaderLengthForAllEventTypes=[];
    }

    public function getHumanReadableDescription()
    {
        $s= 'Create Timestamp: '.$this->createTimestamp.' ('.date('Y-m-d H:i:s',$this->createTimestamp).') '
            .'Header Length: '.$this->headerLength.PHP_EOL
            .'Binlog Version: '.$this->binlogVersion .' Server Version: '.$this->serverVersion.PHP_EOL;
        $s.="Post Header Length For All Event Types:".PHP_EOL;
        foreach ($this->postHeaderLengthForAllEventTypes as $eventType => $postHeaderLengthForAllEventType) {
            $s.=BRKit::hexOneNumber($eventType)."\t".BinlogV4EventHeaderEntity::parseTypeName($eventType)
                .' : '.$postHeaderLengthForAllEventType
                .' : '.BRKit::hexOneNumber($postHeaderLengthForAllEventType)
                .PHP_EOL;
        }
        $s.="The last unknown byte: ".BRKit::hexOneNumber($this->theLastUnknownByte);
        return $s;
    }

    /**
     * @param BinlogReader $reader
     * @return FormatDescriptionEventEntity
     * @throws Exception
     */
    public function readFromBinlogStream($reader){
        $this->binlogVersion=$reader->readNumber(2);
        $this->serverVersion=$reader->readString(50);
        $this->createTimestamp=$reader->readNumber(4);
        $this->headerLength=$reader->readNumber(1);

        // a array indexed by (Binlog Event Type - 1) to extract the length of the event specific header.
        // originally: $typeCount=$this->header->eventLength-76;
        // however the defined event types, other than 0x00, is 0x01 ~ 0x23, totally 23
        // $typeCount=0x23;
        // but I think I should consider the checksum tail
        $typeCount=$this->header->eventLength-76-self::checksumByteCount()-1;
        for($i=1;$i<=$typeCount;$i++){
            $this->postHeaderLengthForAllEventTypes[$i]=$reader->readNumber(1);
        }

        // TODO I wonder why there is one more byte (as I found is 0x01), I now try to eat it and ignore it.
        $this->theLastUnknownByte=$reader->readNumber(1);

        return $this;
    }
}