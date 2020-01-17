<?php


namespace sinri\BinlogReader\entity\MySqlType;


class Timestamp2Type extends BaseType
{

    protected $fsp;

    /**
     * @inheritDoc
     */
    public function parseValue($metaBuffer, $buffer, &$outputLength = null)
    {
        $this->fsp = $metaBuffer->readNumberWithSomeBytesLE(0, 1);
        $outputLength = 4;
        if ($this->fsp >= 5) {
            $outputLength += 3;
        } elseif ($this->fsp >= 3) {
            $outputLength += 2;
        } elseif ($this->fsp > 0) {
            $outputLength += 1;
        }

        $x = $buffer->readNumberWithSomeBytesBE(0, 4);
        if ($outputLength > 4) {
            $y = $buffer->readNumberWithSomeBytesBE(4, $outputLength - 4);
        } else {
            $y = 0;
        }
        return $x . '.' . $y;
    }
}