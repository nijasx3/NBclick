<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\TraceableAdapter;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class MatchsControllerTest extends WebTestCase
{
    public function testIndexReturnsSuccessfulResponse(): void
    {
        $client = static::createClient();
        $client->request('GET', '/matchs/2024/01/30');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('body');
    }

    public function testMatchDoneReturnsSuccessfulResponse(): void
    {
        $client = static::createClient();
        $client->request('GET', '/matchs-done/2024/01/29');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('body');
    }

    public function testIndexHandlesInvalidDate(): void
    {
        $client = static::createClient();
        $client->request('GET', '/matchs/invalid-date');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testMatchDoneForFutureDateThrowsException(): void
    {
        
        $client = static::createClient();

        // Utiliser TraceableAdapter comme cache
        $mockCache = new TraceableAdapter(new ArrayAdapter());

        
        $mockCache->get('games_schedule_2025_01_31', function () {
            return [
                'games' => [
                    ['id' => 'game1', 'teams' => ['Team A', 'Team B'], 'status' => 'scheduled'],
                ],
            ];
        });

        // Remplacer le service CacheInterface par notre TraceableAdapter
        self::getContainer()->set(CacheInterface::class, $mockCache);

        // Récupérer la date future
        $futureDate = (new \DateTime('+1 day'))->format('Y/m/d');

        // Empêcher l'exception de stopper l'exécution pour la gestion des erreurs
        $client->catchExceptions(false);

        // Attendre une exception NotFoundHttpException pour les matchs dans le futur
        $this->expectException(NotFoundHttpException::class);

        // Effectuer la requête GET avec la date future
        $client->request('GET', "/matchs-done/{$futureDate}");
    }
}
