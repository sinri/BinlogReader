<?php

use Psr\Log\LogLevel;
use sinri\ark\core\ArkLogger;
use sinri\BinlogReader\BinlogReader;
use sinri\BinlogReader\BREnv;

require_once __DIR__ . '/../vendor/autoload.php';

date_default_timezone_set("Asia/Shanghai");

$filename = "/path/to/mysql-bin.log.file";

$logger = new ArkLogger(__DIR__ . '/../debug/log', '', null);
$logger->setIgnoreLevel(LogLevel::DEBUG);

// for debug cleaning old log contents
if (file_exists($logger->getCurrentLogFilePath())) {
    @unlink($logger->getCurrentLogFilePath());
}

BREnv::setChecksumMode(BREnv::CHECKSUM_CRC32);
BREnv::setLogger($logger);

(new BinlogReader($filename))
    ->openFile()
    ->parseToEntity()
    ->closeFile();
