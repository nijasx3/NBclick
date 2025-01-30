<?php

namespace App\Tests;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class BetControllerTest extends WebTestCase
{
    public function testSomething(): void
    {
        $client = static::createClient();

        $userRepository = static::getContainer()->get(UserRepository::class);

        $testUser = $userRepository->findOneByEmail('anaisjnse@outlook.fr');

        $client->loginUser($testUser);

        $crawler = $client->request('GET', '/bets');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Mes paris');
    }
}
