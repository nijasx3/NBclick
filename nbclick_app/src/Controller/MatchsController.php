<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class MatchsController extends AbstractController
{
    #[Route('/matchs/{date}', name: 'app_matchs', requirements: ['date' => '\d{4}/\d{2}/\d{2}'])]
    public function index(string $date = '2025/01/26'): Response
    {
        $apiKey = $_ENV['SPORTRADAR_API_KEY'];

        $gamesUrl = 'https://api.sportradar.com/nba/trial/v8/en/games/' . $date . '/schedule.json?api_key=' . $apiKey;
        $gamesResponse = file_get_contents($gamesUrl);
        $gamesData = json_decode($gamesResponse, true);

        $teamColors = $this->fetchTeamColors();

        $cotes = [0.98,1.21, 1.36, 1.59, 2.01, 2.68, 3.01, 3.23, 3.87, 3.99, 4.05];

        foreach ($gamesData['games'] as &$game) {
            $homeTeamId = $game['home']['id'];
            $awayTeamId = $game['away']['id'];

            $home_cote = $cotes[array_rand($cotes, 1)];
            $game['home']['cote']=  $home_cote;
            $game['away']['cote']= (5-$home_cote);

            $game['home']['team_colors'] = $teamColors[$homeTeamId]['colors'] ?? ['primary' => '#000000', 'secondary' => '#FFFFFF'];
            $game['away']['team_colors'] = $teamColors[$awayTeamId]['colors'] ?? ['primary' => '#000000', 'secondary' => '#FFFFFF'];
        }


        $currentDate = \DateTime::createFromFormat('Y/m/d', $date);
        $previousDate = $currentDate->modify('-1 day')->format('Y/m/d');
        $nextDate = $currentDate->modify('+2 day')->format('Y/m/d');


        $formattedDate = $currentDate->format('d F Y');

        return $this->render('matchs/index.html.twig', [
            'games' => $gamesData['games'] ?? [],
            'currentDate' => $formattedDate,
            'previousDate' => $previousDate,
            'nextDate' => $nextDate,
        ]);
    }



    private function fetchTeamColors(): array
    {
        $apiKey = $_ENV['SPORTRADAR_API_KEY'];

        $url = 'https://api.sportradar.com/nba/trial/v8/en/league/hierarchy.json?api_key=' . $apiKey;
        $response = file_get_contents($url);
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
    }
}
