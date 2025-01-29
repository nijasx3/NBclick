<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

final class MatchsController extends AbstractController
{
    private CacheInterface $cache;

    public function __construct(CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    private function fetchTeamColors(): array
    {
        return $this->cache->get('team_colors', function (ItemInterface $item) {
            $item->expiresAfter(86400);

            $apiKey = $_ENV['SPORTRADAR_API_KEY'];
            $url = 'https://api.sportradar.com/nba/trial/v8/en/league/hierarchy.json?api_key=' . $apiKey;

            $response = @file_get_contents($url);
            if ($response === false) {
                return [];
            }

            $data = json_decode($response, true);
            $teams = [];

            foreach ($data['conferences'] as $conference) {
                foreach ($conference['divisions'] as $division) {
                    foreach ($division['teams'] as $team) {
                        $teams[$team['id']] = [
                            'colors' => [
                                'primary' => $team['team_colors'][0]['hex_color'] ?? '#000000',
                                'secondary' => $team['team_colors'][1]['hex_color'] ?? '#FFFFFF',
                            ],
                        ];
                    }
                }
            }

            return $teams;
        });
    }
    private function fetchGamesSchedule(string $date): array
    {
        return $this->cache->get('games_schedule_' . str_replace('/', '_', $date), function (ItemInterface $item) use ($date) {
            $item->expiresAfter(600);

            $apiKey = $_ENV['SPORTRADAR_API_KEY'];
            $gamesUrl = "https://api.sportradar.com/nba/trial/v8/en/games/{$date}/schedule.json?api_key={$apiKey}";

            $response = @file_get_contents($gamesUrl);
            if ($response === false) {
                return [];
            }

            return json_decode($response, true);
        });
    }

    #[Route('/matchs/{date}', name: 'app_matchs', requirements: ['date' => '\d{4}/\d{2}/\d{2}'])]
    public function index(string $date = null): Response
    {
        $date = $date ?? (new \DateTime())->format('Y/m/d');

        $gamesData = $this->fetchGamesSchedule($date);
        if (empty($gamesData)) {
            return new Response('Impossible de récupérer les matchs.', Response::HTTP_SERVICE_UNAVAILABLE);
        }

        $teamColors = $this->fetchTeamColors();

        $cotes = [0.98, 1.21, 1.36, 1.59, 2.01, 2.68, 3.01, 3.23, 3.87, 3.99, 4.05];

        foreach ($gamesData['games'] as &$game) {
            $homeTeamId = $game['home']['id'];
            $awayTeamId = $game['away']['id'];

            $home_cote = $cotes[array_rand($cotes, 1)];
            $game['home']['cote'] = $home_cote;
            $game['away']['cote'] = (5 - $home_cote);

            $game['home']['team_colors'] = $teamColors[$homeTeamId]['colors'] ?? ['primary' => '#000000', 'secondary' => '#FFFFFF'];
            $game['away']['team_colors'] = $teamColors[$awayTeamId]['colors'] ?? ['primary' => '#000000', 'secondary' => '#FFFFFF'];
        }

        return $this->render('matchs/index.html.twig', [
            'games' => $gamesData['games'] ?? [],
            'currentDate' => (new \DateTime($date))->format('d F Y'),
            'previousDate' => (new \DateTime($date))->modify('-1 day')->format('Y/m/d'),
            'nextDate' => (new \DateTime($date))->modify('+1 day')->format('Y/m/d'),
            'isPreviousDisabled' => (new \DateTime($date))->modify('-1 day') < new \DateTime('today'),
        ]);
    }


    #[Route('/matchs-done/{date}', name: 'app_matchs_done', requirements: ['date' => '\d{4}/\d{2}/\d{2}'])]
    public function matchDone(string $date = '2025/01/26'): Response
    {
        $apiKey = $_ENV['SPORTRADAR_API_KEY'];

        $gamesUrl = 'https://api.sportradar.com/nba/trial/v8/en/games/' . $date . '/schedule.json?api_key=' . $apiKey;
        $gamesResponse = file_get_contents($gamesUrl);
        $gamesData = json_decode($gamesResponse, true);

        if (!isset($gamesData['games'])) {
            throw $this->createNotFoundException('No games found for the provided date.');
        }

        $teamColors = $this->fetchTeamColors();

        $finishedGames = array_filter($gamesData['games'], function ($game) {
            return $game['status'] === 'closed';
        });

        foreach ($finishedGames as &$game) {
            $homeTeamId = $game['home']['id'];
            $awayTeamId = $game['away']['id'];

            $game['home']['team_colors'] = $teamColors[$homeTeamId]['colors'] ?? ['primary' => '#000000', 'secondary' => '#FFFFFF'];
            $game['away']['team_colors'] = $teamColors[$awayTeamId]['colors'] ?? ['primary' => '#000000', 'secondary' => '#FFFFFF'];
        }

        $currentDate = \DateTime::createFromFormat('Y/m/d', $date);
        $yesterday = new \DateTime('yesterday');


        if ($currentDate > new \DateTime()) {
            throw $this->createNotFoundException('No finished games available for future dates.');
        }

        $previousDate = (clone $currentDate)->modify('-1 day');
        $nextDate = (clone $currentDate)->modify('+1 day');

        $isPreviousDisabled = $previousDate < new \DateTime('2025-01-01');
        $isNextDisabled = $currentDate >= $yesterday;

        return $this->render('matchs/done.html.twig', [
            'games' => $finishedGames,
            'currentDate' => $currentDate->format('d F Y'),
            'previousDate' => $previousDate->format('Y/m/d'),
            'nextDate' => $nextDate->format('Y/m/d'),
            'isPreviousDisabled' => $isPreviousDisabled,
            'isNextDisabled' => $isNextDisabled,
        ]);
    }
}
