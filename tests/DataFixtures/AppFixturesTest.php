<?php

namespace App\Tests\DataFixtures;

use App\DataFixtures\AppFixtures;
use App\Entity\DigitalProduct;
use App\Entity\PhysicalProduct;
use Doctrine\Persistence\ObjectManager;
use PHPUnit\Framework\TestCase;

class AppFixturesTest extends TestCase
{
    public function testLoad(): void
    {
        // Create a mock of ObjectManager
        $manager = $this->createMock(ObjectManager::class);
        
        // Set expectations for persist method to be called for each product (6 times total)
        $manager->expects($this->exactly(6))
            ->method('persist')
            ->withConsecutive(
                [$this->isInstanceOf(PhysicalProduct::class)], // First physical product
                [$this->isInstanceOf(PhysicalProduct::class)], // Second physical product
                [$this->isInstanceOf(PhysicalProduct::class)], // Third physical product
                [$this->isInstanceOf(DigitalProduct::class)],  // First digital product
                [$this->isInstanceOf(DigitalProduct::class)],  // Second digital product
                [$this->isInstanceOf(DigitalProduct::class)]   // Third digital product
            );
        
        // Set expectation for flush to be called once
        $manager->expects($this->once())
            ->method('flush');
        
        // Create fixtures and load them
        $fixtures = new AppFixtures();
        $fixtures->load($manager);
    }
}
