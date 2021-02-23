<?php

namespace App\Domain\Service;

use Psr\Log\LoggerInterface;

class LogService
{
    protected LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function info(string $message, array $context = []): void
    {
        $this->logger->info($message, $context);
    }

    public function error(string $message): void
    {
        $this->logger->error($message);
    }
}
