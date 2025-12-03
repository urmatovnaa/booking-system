<?php

namespace App\Message;

class ResourceCreatedMessage
{
    public function __construct(
        private int $resourceId,
        private string $name
    ) {}

    public function getResourceId(): int { return $this->resourceId; }
    public function getName(): string { return $this->name; }
}
