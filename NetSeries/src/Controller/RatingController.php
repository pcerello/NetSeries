<?php

namespace App\Controller;

use App\Entity\Rating;
use App\Entity\Series;
use App\Entity\User;
use App\Form\RatingType;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Masterminds\HTML5\Entities;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

#[Route('/rating')]
class RatingController extends AbstractController
{
    #[Route('/', name: 'app_rating_index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $ratings = $entityManager
            ->getRepository(Rating::class)
            ->findAll();

        return $this->render('rating/index.html.twig', [
            'ratings' => $ratings,
        ]);
    }

    #[Route('/new/{idSerie}', name: 'app_rating_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $rating = new Rating();

        # Récupère la série avec l'ID passé en paramètre de l'URL
        $series = $entityManager->getRepository(Series::class)->find($request->get('idSerie'));
        /** @var \App\Entity\User */
        $user = $this->getUser();

        # Associe la note à la série et à l'utilisateur
        $rating->setSeries($series);
        $rating->setUser($user);

        # Création du formulaire pour la note
        $form = $this->createForm(RatingType::class, $rating);

        $form->handleRequest($request);

        # Si le formulaire est soumis et valide
        if ($form->isSubmitted() && $form->isValid()) {
            # Enregistrement de la note et mise à jour de la base de données
            $entityManager->persist($rating);
            $entityManager->flush();

            # Redirection vers la liste des notes
            return $this->redirectToRoute('app_series_show', ['id' => $request->get('idSerie')], Response::HTTP_SEE_OTHER);
        }
        
        # Ajoute la note à l'utilisateur et à la série
        $user->addRating($rating);
        $series->addRating($rating);

        # Affichage du formulaire pour la note
        return $this->renderForm('rating/new.html.twig', [
            'rating' => $rating,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_rating_show', methods: ['GET'])]
    public function show(Rating $rating): Response
    {
        

        return $this->render('rating/show.html.twig', [
            'rating' => $rating,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_rating_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Rating $rating, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(RatingType::class, $rating);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_rating_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('rating/edit.html.twig', [
            'rating' => $rating,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_rating_delete', methods: ['POST'])]
    public function delete(Request $request, Rating $rating, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$rating->getId(), $request->request->get('_token'))) {
            $entityManager->remove($rating);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_rating_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/generateCritic/{id}', name:'generateCritic', methods: ['POST'])]
    public function generateCritic(Request $request, EntityManagerInterface $em) : Response
    {

        // Récupère le nombre de critique à générer
        $critic_count = $request->request->get('critic_count');

        // Récupère tous les utilisateurs
        $users = $em->getRepository(User::class)->findAll();

        $series = $em->getRepository(Series::class)->findAll();

        shuffle($series);

        // Sélectionne un utilisateur aléatoire
        $randomUser = array_pop($users);

        // Taille du lot pour l'insertion en base de données pour une meilleur optimisation
        $batchSize = 20;

        // Boucle pour générer un nombre d'utilisateurs
        for ($i = 0; $i < $critic_count; $i++) {
            $rating = new Rating();
            
            $rating->setUser($randomUser);

            $rating->setSeries($series[$i]);

            $rating->setValue(rand(0, 10));


            $em->persist($rating);

        }

        // mise à jour de la base de données
        $em->flush();
        $em->clear();

        // redirige vers la page d'index des utilisateurs
        return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
    }
}
