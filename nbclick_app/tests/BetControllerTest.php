<?php

namespace App\Tests;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class BetControllerTest extends WebTestCase
{
    // Test 1 : User logged in should access their bet page
    public function testUserBetPage(): void
    {
        $client = static::createClient();

        $userRepository = static::getContainer()->get(UserRepository::class);

        $testUser = $userRepository->findOneByEmail($_ENV['USER_TEST']);

        $client->loginUser($testUser);

        $crawler = $client->request('GET', '/bets');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Mes Paris');
    }
}
