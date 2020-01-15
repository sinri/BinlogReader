<?php


namespace sinri\BinlogReader\entity\MySqlType;


abstract class BaseIntegerType extends BaseType
{
    /**
     * @return int
     */
    abstract protected function getByteCount();

    /**
     * @inheritDoc
     */
    public function parseValue($metaBuffer, $buffer, &$outputLength = null)
    {
        $outputLength = $this->getByteCount();
        return $buffer->readNumberWithSomeBytesLE(0, $outputLength);
    }
}