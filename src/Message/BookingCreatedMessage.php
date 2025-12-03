<?php

namespace App\Message;

class BookingCreatedMessage
{
    public function __construct(
        private int $bookingId,
        private int $userId,
        private int $resourceId
    ) {}

    public function getBookingId(): int { return $this->bookingId; }
    public function getUserId(): int { return $this->userId; }
    public function getResourceId(): int { return $this->resourceId; }
}
