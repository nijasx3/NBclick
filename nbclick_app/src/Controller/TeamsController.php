<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class TeamsController extends AbstractController
{
    #[Route('/teams', name: 'app_teams')]
    public function index(): Response
    {

        $teams = $this->fetchTeamColors();

        return $this->render('teams/index.html.twig', [
            'controller_name' => 'TeamsController',
            'teams' => $teams,
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
                        'name' => $team['name'],
                        'market' => $team['market'],
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
