<?php

// namespace App\Tests\Controller;

// use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
// use Symfony\Component\Cache\Adapter\ArrayAdapter;
// use Symfony\Component\Cache\Adapter\TraceableAdapter;
// use Symfony\Contracts\Cache\CacheInterface;
// use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

// class MatchsControllerTest extends WebTestCase
// {
//     public function testIndexReturnsSuccessfulResponse(): void
//     {
//         $client = static::createClient();
//         $client->request('GET', '/matchs/2024/01/30');

//         $this->assertResponseIsSuccessful();
//         $this->assertSelectorExists('body');
//     }

//     public function testMatchDoneReturnsSuccessfulResponse(): void
//     {
//         $client = static::createClient();
//         $client->request('GET', '/matchs-done/2024/01/29');

//         $this->assertResponseIsSuccessful();
//         $this->assertSelectorExists('body');
//     }

//     public function testIndexHandlesInvalidDate(): void
//     {
//         $client = static::createClient();
//         $client->request('GET', '/matchs/invalid-date');

//         $this->assertResponseStatusCodeSame(404);
//     }
// }
