<?php


namespace sinri\BinlogReader\entity\MySqlType;


use Exception;
use sinri\BinlogReader\BRByteBuffer;
use sinri\BinlogReader\BREnv;

class MixedBufferType extends BaseType
{
    protected $lengthByteCount;
    protected $valueByteCount;
    /**
     * @var BRByteBuffer
     */
    protected $contentByteBuffer;

    /**
     * @inheritDoc
     */
    public function parseValue($metaBuffer, $buffer, &$outputLength = null)
    {
        $this->valueByteCount = $buffer->readLenencInt(0, $this->lengthByteCount);
        $this->contentByteBuffer = $buffer->getSubByteBuffer($this->lengthByteCount, $this->valueByteCount);
        $outputLength = $this->valueByteCount + $this->lengthByteCount;
        return $this->contentByteBuffer;
    }

    public final function readByteInBuffer($index, $default = 0)
    {
        try {
            return $this->contentByteBuffer->readNumberWithSomeBytesLE($index, 1);
        } catch (Exception $exception) {
            BREnv::getLogger()->warning(__METHOD__ . '@' . __LINE__ . ' use default value: ' . $exception->getMessage());
            return $default;
        }
    }
}