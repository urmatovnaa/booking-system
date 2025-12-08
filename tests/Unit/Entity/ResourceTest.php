<?php
// tests/Unit/Entity/ResourceTest.php
namespace App\Tests\Unit\Entity;

use App\Entity\Resource;
use PHPUnit\Framework\TestCase;

class ResourceTest extends TestCase
{
    public function testResourceCreation(): void
    {
        $resource = new Resource();
        $resource->setName('Conference Room');
        $resource->setDescription('Large room with projector');
        
        $this->assertEquals('Conference Room', $resource->getName());
        $this->assertEquals('Large room with projector', $resource->getDescription());
    }
    
    public function testResourceStatus(): void
    {
        $resource = new Resource();
        
        $resource->setStatus('active');
        $this->assertEquals('active', $resource->getStatus());
        
        $resource->setStatus('inactive');
        $this->assertEquals('inactive', $resource->getStatus());
        
        $resource->setStatus('maintenance');
        $this->assertEquals('maintenance', $resource->getStatus());
    }
}