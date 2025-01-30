<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class MainControllerTest extends WebTestCase
{
    public function testHomePageLoadsSuccessfully(): void
    {
        $client = static::createClient();
        $client->request('GET', '/'); 

        $this->assertResponseIsSuccessful(); // Vérifie que le code HTTP est 200
        $this->assertSelectorTextContains('h1', 'Bienvenue'); // Vérifie un élément
    }
}
