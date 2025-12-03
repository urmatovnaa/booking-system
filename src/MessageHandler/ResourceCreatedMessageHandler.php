<?php

namespace App\MessageHandler;

use App\Message\ResourceCreatedMessage;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class ResourceCreatedMessageHandler
{
    public function __construct(private LoggerInterface $logger) {}

    public function __invoke(ResourceCreatedMessage $message)
    {
        $this->logger->info("📩 Новый ресурс создан: {$message->getName()} (ID: {$message->getResourceId()})");
    }
}
