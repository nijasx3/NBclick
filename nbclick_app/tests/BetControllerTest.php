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

        $testUser = $userRepository->findOneByEmail('anaisjnse@outlook.fr');

        $client->loginUser($testUser);

        $crawler = $client->request('GET', '/bets');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Mes Paris');
    }


    // Test 2.0 : user adds a valid bet
    public function testAddBet(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);

        // Simulate a logged-in user
        $testUser = $userRepository->findOneByEmail('anaisjnse@outlook.fr');
        $client->loginUser($testUser);

        // Define test match ID (replace with a valid match ID)
        $idMatch = '863d255f-c81a-46d9-a991-6acbff6c0ea3'; 
        $team = 'home'; // Change based on actual API response structure
        $cote = 1.75; 

        // Step 1: Make a GET request to ensure the form loads correctly
        $crawler = $client->request('GET', "/bets/add/$idMatch?team=$team&cote=$cote");

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form'); // Check if form is present

        // Step 2: Fill in and submit the form
        $form = $crawler->filter('form')->form([
            'bet[priceBet]' => 10, // Adjust based on the form structure
        ]);

        $client->submit($form);

        // Check if the response is a redirection (valid bet)
        $this->assertResponseRedirects('/bets');

        // Follow the redirect and check for success message
        $client->followRedirect();
       // $this->assertSelectorTextContains('.alert-success', 'Pari confirmé.');
    }

}
