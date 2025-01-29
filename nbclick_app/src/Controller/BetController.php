<?php

namespace App\Controller;

use App\Entity\Bet;
use App\Form\BetType;
use App\Repository\BetRepository;
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
    public function index(Bet $bet, BetRepository $betRepository): Response
    {

        // récuperer tous les paris

        // récuperer paris en cours et requête par id sur api

        // récupérer paris terminés et les alimenter avec partie Simon sur les match terminés

        return $this->render('bet/index.html.twig', [
            'controller_name' => 'BetController',
        ]);
    }

    // #[Route('/addbet/{id_match}', name: 'app_new_bet', methods: ['GET'])]
    // public function add(EntityManagerInterface $entityManager, string $id_match, Request $request): Response
    // {

    //     // get info du match pour faire récapitulatif du match

    //     $apiKey = $_ENV['SPORTRADAR_API_KEY'];

    //     $gamesUrl = 'https://api.sportradar.com/nba/trial/v8/en/games/' . $id_match . '/summary.json?api_key=' . $apiKey;
    //     $gamesResponse = file_get_contents($gamesUrl);
    //     $gamesData = json_decode($gamesResponse, true);

    //     //var_dump($gamesData);
    //     // get info du pari par le user

    //     $team_in_url = $request->query->get('team');
    //     $cote = $request->query->get('cote');
    //     $user = $this->getUser();

    //     $supported_team = $gamesData[$team_in_url];
    //     //var_dump($supported_team);


    //     //afficher le formulaire de la mise

    //     //envoyer id paris, id user, choix, mise, cote, résultat, date

    //     $bet = new Bet();

    //     $form = $this->createForm(BetType::class, $bet);
    //     $form->add('submit', SubmitType::class, [
    //         'label' => 'Valider le pari',
    //         'attr' => ['class' => 'btn btn-primary']
    //     ]);

    //     $form->handleRequest($request);
    //     if ($form->isSubmitted() && $form->isValid()) {

    //         $bet = $form->getData();
    //         $bet->setIdMatch($id_match);
    //         $bet->setIdUser($user);
    //         $bet->setBetBet($team_in_url);
    //         $bet->setOddsBet($cote);
    //         $bet->setResultBet('en cours');


    //         $entityManager->persist($bet);
    //         $entityManager->flush();

    //         $this->addFlash('success', 'Pari confirmé.');
    //         return $this->redirectToRoute('app_bets');
    //     } elseif ($form->isSubmitted()) {
    //         $this->addFlash('error', 'Le post n\'a pas été ajouté.');
    //     }



    //     // update solde user



    //     $form = '';
    //     return $this->render('bet/new.html.twig', [
    //         'form' => $form
    //     ]);
    // }

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
        $user = $this->getUser();

        $supported_team = $gamesData[$team_in_url];

        // Vérifier si l'équipe existe dans la réponse API
        if (!isset($supported_team)  ) {
            throw $this->createNotFoundException('Match invalide.');
        }

        // Créer un nouveau pari
        $bet = new Bet();
        $bet->setIdMatch($id_match);
        $bet->setIdUser($user);
        $bet->setBetBet($team_in_url);
        $bet->setOddsBet($cote);
        $bet->setResultBet('en cours');

        // Créer le formulaire
        $form = $this->createForm(BetType::class, $bet);
        $form->handleRequest($request);

        // Traiter le formulaire
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($bet);
            $entityManager->flush();

            $this->addFlash('success', 'Pari confirmé.');
            return $this->redirectToRoute('app_bets');
        } elseif ($form->isSubmitted()) {
            $this->addFlash('error', 'Le post n\'a pas été ajouté.');
        }

        // Rendre la vue avec le formulaire
        return $this->render('bet/new.html.twig', [
            'form' => $form->createView(), // Passer le formulaire à Twig
            'game' => $gamesData,
            'team' => $team_in_url,
            'cote' => $cote,
        ]);
    }

    //  {
    //      //instanciation d'un nouveau post
    //      $post = new Post();

    //      //création du formulaire dans la page
    //      $form = $this->createForm(PostType::class, $post);
    //      $form->add('submit',SubmitType::class, [
    //          'label'=>'Créer un post',
    //          'attr'=>['class'=>'btn btn-primary']
    //      ]);

    //      // recognizes submission and writes submitted data into the properties of the $post object
    //      $form->handleRequest($request);
    //      if ($form->isSubmitted() && $form->isValid()) {
    //          $post = $form->getData();

    //          $entityManager->persist($post);
    //          $entityManager->flush();

    //          $this->addFlash('success','Le post a bien été ajouté.');
    //          return $this->redirectToRoute('new_post');
    //      }
    //      elseif ($form->isSubmitted()){
    //          $this->addFlash('error','Le post n\'a pas été ajouté.');
    //      }

    //      return $this->render('post/new.html.twig', [
    //          'form' => $form,
    //      ]);
    //  }
}
