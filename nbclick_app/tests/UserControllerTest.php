<?php

namespace App\Tests;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class UserControllerTest extends WebTestCase
{

    // Test 1.0 : user should access their profile while logged in
    public function testShowUserProfileLoggedIn(): void
    {
        $client = static::createClient();

        $userRepository = static::getContainer()->get(UserRepository::class);

        $testUser = $userRepository->findOneByEmail($_ENV['USER_TEST']);

        $client->loginUser($testUser);

        $client->request('GET', '/user/profile');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'My profile');
    }

    // Test 1.1 : user should not access profile if they're not logged in -> redirect to login page
    public function testUserProfileRedirectIfNotLoggedIn(): void
    {
        $client = static::createClient();

        $client->request('GET', '/user/profile');

        $this->assertResponseRedirects('/login');

        $client->followRedirect();

        $this->assertSelectorExists('form[name="login"]');
    }

    // Test 2.0 : user should access the page for updating their profile
    public function testShowUserUpdateInformation(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);

        $testUser = $userRepository->findOneByEmail($_ENV['USER_TEST']);

        $client->loginUser($testUser);

        $client->request('GET', '/user/update');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Modifier');
    }

    // Test 2.1 : user should be redirected to their profile on successful submission of update
    public function testPostUserUpdateInformation(): void
    {
        // Step 1: Acces update page as logged in user
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);

        $testUser = $userRepository->findOneByEmail($_ENV['USER_TEST']);

        $client->loginUser($testUser);

        $crawler = $client->request('GET', '/user/update');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Modifier');


        // Step 2: Submit the form with new data
        $form = $crawler->filter('form')->form([  // Adjust the button name as needed
            'user[first_name_user]' => 'New Name',
            'user[cash_balance_user]' => '12'
        ]);

        $client->submit($form);

        // Step 3: Assert redirection after form submission
        $this->assertResponseRedirects('/user/profile'); 

        // Step 4: Follow the redirection
        $client->followRedirect();

        // Step 5: Assert that the confirmation message is displayed
       $this->assertSelectorTextContains('h1', 'My profile'); 

    }
}
