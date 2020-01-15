<?php


namespace sinri\BinlogReader;


use sinri\ark\core\ArkLogger;

class BREnv
{
    /**
     * @var ArkLogger
     */
    protected static $logger = null;

    /**
     * @return ArkLogger
     */
    public static function getLogger(): ArkLogger
    {
        if (self::$logger === null) {
            self::$logger = ArkLogger::makeSilentLogger();
        }
        return self::$logger;
    }

    /**
     * @param ArkLogger $logger
     */
    public static function setLogger(ArkLogger $logger)
    {
        self::$logger = $logger;
    }

    const CHECKSUM_NONE = 'NONE';
    const CHECKSUM_CRC32 = 'CRC32';

    static protected $checksumMode = self::CHECKSUM_CRC32;

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

    public static function checksumByteCount()
    {
        switch (self::$checksumMode) {
            case self::CHECKSUM_CRC32:
                return 4;
            default:
                return 0;
        }
    }
}