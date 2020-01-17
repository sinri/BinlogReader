<?php


namespace sinri\BinlogReader;


use Exception;
use sinri\ark\core\ArkHelper;
use sinri\ark\core\ArkLoggerBufferForRepeatJobDebug;
use sinri\BinlogReader\entity\BaseEventEntity;
use sinri\BinlogReader\entity\BinlogV4EventHeaderEntity;

class BinlogReader
{
    protected $binlogFile;
    protected $binlogFileHandler;

    protected $parseStartTimestamp;
    protected $parseEndTimestamp;
    /**
     * @var callable function($event,$eventIndex)
     */
    protected $eventHandler;

    /**
     * @return callable
     */
    public function getEventHandler()
    {
        return $this->eventHandler;
    }

    /**
     * @param callable $eventHandler
     * @return BinlogReader
     */
    public function setEventHandler($eventHandler): BinlogReader
    {
        $this->eventHandler = $eventHandler;
        return $this;
    }

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
        $this->parseStartTimestamp = microtime(true);

        // file head
        $magic_number_chars = fread($this->binlogFileHandler, 4);
        ArkHelper::quickNotEmptyAssert("Not a valid MySQL Binlog File Header: " . BRKit::hexString($magic_number_chars), ($magic_number_chars === "\xfe\x62\x69\x6e"));

        // logger buffer
        $loggerBuffer = new ArkLoggerBufferForRepeatJobDebug(null, true, BREnv::getLogger());
        BREnv::getLogger()->setBuffer($loggerBuffer);

        // events
        $eventIndex = 0;
        while (true) {
            $header = null;
            $bodyBuffer = null;
            $checksum = null;
            try {
                $currentFileOffset = ftell($this->binlogFileHandler);
                BREnv::getLogger()->info("Ready for next block", ['offset' => $currentFileOffset]);

                $this->showProgress("Reading Next Block");

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

                $this->showProgress("Parsing Next Block");

                $event = BaseEventEntity::parseNextEvent($header, $bodyBuffer, $checksum);
                BREnv::getLogger()->info("" . $event);
                //$this->events[] = $event;

                BREnv::getLogger()->sendCommandToBuffer(ArkLoggerBufferForRepeatJobDebug::COMMAND_REPORT_NORMAL);
                $this->showProgress("Parsed This Block");

                if ($this->eventHandler !== null) {
                    $handleResult = call_user_func_array($this->eventHandler, [$event, $eventIndex]);
                    BREnv::getLogger()->info("Handler Processed", ['result' => $handleResult]);
                }

                $eventIndex++;
            } catch (Exception $exception) {
                BREnv::getLogger()->error("ERROR WHILE READING: " . $exception->getMessage());
                BREnv::getLogger()->error("HEADER\t: " . PHP_EOL . $header);
                BREnv::getLogger()->error("BODY\t: " . PHP_EOL . $bodyBuffer);
                BREnv::getLogger()->error("CHECKSUM\t:" . $checksum);

                BREnv::getLogger()->sendCommandToBuffer(ArkLoggerBufferForRepeatJobDebug::COMMAND_REPORT_ERROR);

                $this->showProgress("Error in parsing this block");
                throw $exception;
            }
        }

        $this->showProgress("Completed");

        $this->parseEndTimestamp = microtime(true);

        BREnv::getLogger()->setBuffer(null);

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

    public function showProgress($message)
    {
        $offset = ftell($this->binlogFileHandler);
        $whole = filesize($this->binlogFile);
        $percent = number_format(100.0 * $offset / $whole, 2);

        $currentTimestamp = microtime(true);
        $cost = ($currentTimestamp - $this->parseStartTimestamp);
        $predictMore = number_format(1.0 * $cost / $offset * ($whole - $offset), 2);

        echo "\rCurrent Bytes: " . $offset . " ({$percent}%)\t Time Cost: {$cost}s\t Predict Ends in {$predictMore}s \t Message: " . $message;
    }

}