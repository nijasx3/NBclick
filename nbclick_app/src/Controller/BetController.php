<?php

namespace App\Controller;

use App\Entity\Bet;
use App\Entity\User;
use App\Form\BetType;
use App\Repository\BetRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

use function PHPUnit\Framework\isEmpty;

final class BetController extends AbstractController
{
    #[Route('/bets', name: 'app_bets')]
    public function index(BetRepository $betRepository, EntityManagerInterface $entityManager): Response
    {

        $apiKey = $_ENV['SPORTRADAR_API_KEY'];

        // find latest season
        $seasonUrl = 'https://api.sportradar.com/nba/trial/v8/en/league/seasons.json?api_key=' . $apiKey;
        $seasonRes = file_get_contents($seasonUrl);
        $seasonData = json_decode($seasonRes, true);

        function getLatestSeason(array $seasons)
        {
            return array_reduce($seasons, function ($latest, $season) {
                return ($latest === null || $season['year'] > $latest['year']) ? $season : $latest;
            }, null);
        }

        $latestSeason = getLatestSeason($seasonData['seasons']);


        // find games of the season

        $gamesUrl = 'https://api.sportradar.com/nba/trial/v8/en/games/' . $latestSeason['year'] . '/REG/schedule.json?api_key=' . $apiKey;
        $gamesResponse = file_get_contents($gamesUrl);
        $gamesData = json_decode($gamesResponse, true);


        // get user's bets

        /** @var User */
        $user = $this->getUser();
        $bets = $user->getBets();


        function findGameById(array $games, string $id)
        {
            foreach ($games as $game) {
                if ($game['id'] === $id) {
                    return $game; // Retourne directement le match trouvé
                }
            }
            // return null; // Retourne null si aucun match n'est trouvé
        }



        $info_bets = [];


        foreach ($bets as $bet) {

            $match = $bet->getIdMatch();

            $info_match = findGameById($gamesData['games'], $match);
            dump($info_match);
            array_push($info_bets, ['bet' => $bet, 'match' => $info_match]);

            if ($info_match) {
                if ($info_match['status'] === 'closed') {
                    // Récupérer les scores
                    $homePoints = $info_match['home_points'];
                    $awayPoints = $info_match['away_points'];
                    $betOn = $bet->getBetBet(); // L'équipe sur laquelle l'utilisateur a parié

                    // Déterminer le gagnant
                    $winner = $homePoints > $awayPoints ? 'home' : 'away';
                    if ($betOn === $winner) {
                        $bet->setResultBet('gagné');
                    } else {
                        $bet->setResultBet('perdu');
                    }
                } else {
                    $bet->setResultBet('en cours');
                }
            }
        }


        $entityManager->flush();

        return $this->render('bet/index.html.twig', [
            'controller_name' => 'BetController',
            'bets' => $info_bets
        ]);
    }

    #[Route('/bets/add/{id_match}', name: 'app_new_bet', methods: ['GET', 'POST'])]
    public function add(EntityManagerInterface $entityManager, string $id_match, Request $request): Response
    {
        // Récupérer les infos du match pour le récapitulatif
        $apiKey = $_ENV['SPORTRADAR_API_KEY'];
        $gamesUrl = 'https://api.sportradar.com/nba/trial/v8/en/games/' . $id_match . '/summary.json?api_key=' . $apiKey;
        $gamesResponse = file_get_contents($gamesUrl);
        $gamesData = json_decode($gamesResponse, true);

        // Récupérer les paramètres depuis l'URL
        $team_in_url = $request->query->get('team');
        $cote = $request->query->get('cote');

        /** @var User */
        $user = $this->getUser();

        $supported_team = $gamesData[$team_in_url];

        // Vérifier si l'équipe existe dans la réponse API
        if (!isset($supported_team)) {
            throw $this->createNotFoundException('Match invalide.');
        }

        // Créer un nouveau pari

        $bet = new Bet();

        // Créer le formulaire
        $form = $this->createForm(BetType::class, $bet);
        $form->handleRequest($request);


        $available_credit = intval($user->getCashBalanceUser());


        // Traiter le formulaire
        if ($form->isSubmitted() && $form->isValid()) {

            $bet = $form->getData();
            $bet->setIdMatch($id_match);
            $bet->setIdUser($user);
            $bet->setBetBet($team_in_url);
            $bet->setOddsBet($cote);
            $bet->setResultBet('en cours');
            $bet->setpaidBet(false);
            $bet->setDateMatch($gamesData['scheduled']);

            $price = intval($bet->getPriceBet());

            if ($price > $available_credit) {
                $this->addFlash('error', 'Crédits insuffisants.');
            } else {
                // post pari
                $entityManager->persist($bet);



                // UPDATE USER
                $new_balance = ($available_credit - $price);
                $user->setCashBalanceUser($new_balance);

                //flush tout d'un coup
                $entityManager->flush();

                $this->addFlash('success', 'Pari confirmé.');
                return $this->redirectToRoute('app_bets');
            }
        } elseif ($form->isSubmitted()) {
            $this->addFlash('error', 'Le pari n\'a pas été pris en compte');
        }

        // Rendre la vue avec le formulaire
        return $this->render('bet/new.html.twig', [
            'form' => $form->createView(), // Passer le formulaire à Twig
            'game' => $gamesData,
            'team' => $team_in_url,
            'cote' => $cote,
        ]);
    }
    #[Route('/bets/claim/{id}', name: 'app_bet_claim', methods: ['POST'])]
    public function claimBet(EntityManagerInterface $entityManager, Bet $bet): Response
    {
        /** @var User */
        $user = $this->getUser();


        if ($bet->getIdUser() !== $user) {
            throw $this->createAccessDeniedException('Ce pari ne vous appartient pas.');
        }


        if ($bet->isPaidBet()) {
            $this->addFlash('warning', 'Les gains de ce pari ont déjà été récupérés.');
            return $this->redirectToRoute('app_bets');
        }


        if ($bet->getResultBet() !== 'gagné') {
            $this->addFlash('error', 'Ce pari n\'est pas gagnant.');
            return $this->redirectToRoute('app_bets');
        }


        $gain = $bet->getOddsBet() * $bet->getPriceBet();


        $user->setCashBalanceUser($user->getCashBalanceUser() + $gain);


        $bet->setPaidBet(true);


        $entityManager->flush();

        $this->addFlash('success', 'Vos gains ont été ajoutés à votre solde.');

        return $this->redirectToRoute('app_bets');
    }
}
