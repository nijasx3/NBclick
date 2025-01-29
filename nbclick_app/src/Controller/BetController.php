<?php

namespace App\Controller;

use App\Entity\Bet;
use App\Entity\User;
use App\Form\BetType;
use App\Repository\BetRepository;
use App\Repository\UserRepository;
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
    public function index(BetRepository $betRepository): Response
    {
        
        // récuperer tous les paris

        /** @var User */
        $user = $this->getUser();
        $bets = $user->getBets();

        $info_bets = [];
        foreach ($bets as $bet) {

            // $match = $bet->getIdMatch();
            // $apiKey = $_ENV['SPORTRADAR_API_KEY'];
            // $gamesUrl = 'https://api.sportradar.com/nba/trial/v8/en/games/' . $match . '/summary.json?api_key=' . $apiKey;
            // $gamesResponse = file_get_contents($gamesUrl);
            // $gamesData = json_decode($gamesResponse, true);

            array_push($info_bets, ['bet' => $bet, 'match' => '$gamesData']);
           
        }
        

        // récuperer paris en cours et requête par id sur api

        // récupérer paris terminés et les alimenter avec partie Simon sur les match terminés

        return $this->render('bet/index.html.twig', [
            'controller_name' => 'BetController', 'bets'=>$info_bets
        ]);
    }

    #[Route('/addbet/{id_match}', name: 'app_new_bet', methods: ['GET', 'POST'])]
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

}
