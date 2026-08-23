<?php

require_once __DIR__ . '/../vendor/autoload.php';

class LoggerTest extends PHPUnit\Framework\TestCase
{
    public function testLogPrefixesTimestamp()
    {
        $dateTimeProvider = $this->createMock(Manx\IDateTimeProvider::class);
        $dateTimeProvider->expects($this->once())->method('now')
            ->willReturn(\DateTime::createFromFormat(
                'Y-m-d H:i:s.u',
                '2026-08-23 12:34:56.000000',
                new \DateTimeZone('UTC')));
        $logger = new Manx\Cron\Logger($dateTimeProvider);

        $this->expectOutputString("[2026-08-23 12:34:56] hello\n");
        $logger->log('hello');
    }
}
