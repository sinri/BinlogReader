<?php


namespace sinri\BinlogReader;


use Exception;
use sinri\ark\core\ArkHelper;
use sinri\BinlogReader\entity\BaseEventEntity;
use sinri\BinlogReader\entity\BinlogV4EventHeaderEntity;

class BinlogReader
{
    protected $binlogFile;
    protected $binlogFileHandler;

    protected $events;

    /**
     * BinlogReader constructor.
     * @param $binlogFile
     */
    public function __construct($binlogFile)
    {
        $this->binlogFile = $binlogFile;
    }

    /**
     * @return $this
     * @throws Exception
     */
    public function openFile()
    {
        $this->binlogFileHandler = fopen($this->binlogFile, "rb");
        ArkHelper::quickNotEmptyAssert("Cannot get binlog file read handler", $this->binlogFileHandler);
        return $this;
    }

    /**
     * @return $this
     * @throws Exception
     */
    public function parseToEntity()
    {
        // file head
        $magic_number_chars = fread($this->binlogFileHandler, 4);
        ArkHelper::quickNotEmptyAssert("Not a valid MySQL Binlog File Header", ($magic_number_chars !== "\xfe\x62\x69\6e"));
        // events
        while (true) {
            $header = null;
            $bodyBuffer = null;
            $checksum = null;
            try {
                $currentFileOffset = ftell($this->binlogFileHandler);
                BREnv::getLogger()->debug(__METHOD__ . '@' . __LINE__ . " Ready for next block", ['offset' => $currentFileOffset]);
                $header = $this->readNextHeader();
                if ($header === false) {
                    BREnv::getLogger()->info("Mo more bytes to parse, END");
                    break;
                }
                BREnv::getLogger()->debug(__METHOD__ . '@' . __LINE__ . ' get header', ['header' => $header]);
                $bodyLength = $header->eventLength - 19 - BREnv::checksumByteCount();
                $bodyBuffer = BRByteBuffer::pickFixedBuffer($this->binlogFileHandler, $bodyLength);
                $checksum = null;
                if (BREnv::checksumByteCount() > 0) {
                    $checksum = BRByteBuffer::pickFixedBuffer($this->binlogFileHandler, BREnv::checksumByteCount())->readNumberWithSomeBytesLE(0, 4);
                }

                $event = BaseEventEntity::parseNextEvent($header, $bodyBuffer, $checksum);
                BREnv::getLogger()->info("Event: " . $event);
                $this->events[] = $event;
            } catch (Exception $exception) {
                BREnv::getLogger()->error("ERROR WHILE READING: " . $exception->getMessage() . PHP_EOL . $exception->getTraceAsString());
//                foreach ($exception->getTrace() as $trace){
//                    BREnv::getLogger()->error("↘ ",$trace);
//                }
                BREnv::getLogger()->error("HEADER\t: " . PHP_EOL . $header);
                BREnv::getLogger()->error("BODY\t: " . PHP_EOL . $bodyBuffer);
                BREnv::getLogger()->error("CHECKSUM\t:" . $checksum);
                break;
            }
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function closeFile()
    {
        fclose($this->binlogFileHandler);
        return $this;
    }

    /**
     * @return false|BinlogV4EventHeaderEntity
     * @throws Exception
     */
    protected function readNextHeader()
    {
        try {
            $headerBuffer = BRByteBuffer::pickFixedBuffer($this->binlogFileHandler, 19);
            return new BinlogV4EventHeaderEntity($headerBuffer);
        } catch (Exception $exception) {
            BREnv::getLogger()->warning(__METHOD__ . '@' . __LINE__ . ' ' . $exception->getMessage());
            return false;
        }
    }

}