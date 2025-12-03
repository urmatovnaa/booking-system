<?php

namespace App\MessageHandler;

use App\Message\BookingCreatedMessage;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class BookingCreatedMessageHandler
{
    public function __construct(private LoggerInterface $logger) {}

    public function __invoke(BookingCreatedMessage $message)
    {
        $this->logger->info("📩 Новая бронь создана:(пользователь: {$message->getUserId()}, (ресурс:{$message->getResourceId()}) (ID: {$message->getBookingId()})");
    }
}
