<?php

namespace App\MessageHandler;

use App\Message\UserRegisteredMessage;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class UserRegisteredMessageHandler
{
    public function __construct(private LoggerInterface $logger) {}

    public function __invoke(UserRegisteredMessage $message)
    {
        $this->logger->info("📩 Новый пользователь зарегистрирован: {$message->email} (ID: {$message->userId})");
    }
}