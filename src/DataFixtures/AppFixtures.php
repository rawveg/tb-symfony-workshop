<?php

namespace App\DataFixtures;

use App\Entity\DigitalProduct;
use App\Entity\PhysicalProduct;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Create physical products
        $physical1 = new PhysicalProduct();
        $physical1->setName('Ergonomic Keyboard');
        $physical1->setDescription('A comfortable keyboard designed for long typing sessions with mechanical switches.');
        $physical1->setPrice(149.99);
        $physical1->setSku('KB-ERG-001');
        $physical1->setWeight(1.2);
        $manager->persist($physical1);
        
        $physical2 = new PhysicalProduct();
        $physical2->setName('4K Monitor');
        $physical2->setDescription('Ultra HD monitor with IPS panel and 32-inch screen.');
        $physical2->setPrice(399.99);
        $physical2->setSku('MN-4K-002');
        $physical2->setWeight(5.8);
        $manager->persist($physical2);
        
        $physical3 = new PhysicalProduct();
        $physical3->setName('Wireless Mouse');
        $physical3->setDescription('Precision wireless mouse with long battery life and customizable buttons.');
        $physical3->setPrice(79.99);
        $physical3->setSku('MS-WRL-003');
        $physical3->setWeight(0.3);
        $manager->persist($physical3);
        
        // Create digital products
        $digital1 = new DigitalProduct();
        $digital1->setName('Photoshop Template Bundle');
        $digital1->setDescription('Collection of 50 professional Photoshop templates for various design projects.');
        $digital1->setPrice(29.99);
        $digital1->setDownloadUrl('https://example.com/downloads/photoshop-bundle');
        $digital1->setFileSize(1500000);
        $manager->persist($digital1);
        
        $digital2 = new DigitalProduct();
        $digital2->setName('Programming E-Book');
        $digital2->setDescription('Comprehensive guide to modern PHP development with Symfony framework.');
        $digital2->setPrice(19.99);
        $digital2->setDownloadUrl('https://example.com/downloads/php-ebook');
        $digital2->setFileSize(8500000);
        $manager->persist($digital2);
        
        $digital3 = new DigitalProduct();
        $digital3->setName('Music Production Course');
        $digital3->setDescription('Video course teaching electronic music production from beginner to advanced level.');
        $digital3->setPrice(49.99);
        $digital3->setDownloadUrl('https://example.com/downloads/music-course');
        $digital3->setFileSize(1500000000); // Reduced from 2500000000 to fit in PostgreSQL integer range
        $manager->persist($digital3);
        
        $manager->flush();
    }
}
