<?php

namespace App\Controller;

use App\Entity\Rating;
use App\Entity\Series;
use App\Entity\User;
use App\Form\RatingType;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Masterminds\HTML5\Entities;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormError;
use Knp\Component\Pager\PaginatorInterface;

#[Route('/rating')]
class RatingController extends AbstractController
{
    #[Route('/', name: 'app_rating_index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager, PaginatorInterface $paginator, Request $request): Response
    {
        /** @var App\Entity\User */
        $user = $this->getUser();

        //Si pas de compte connecté alors on lui dit de ce connecté
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        //Si l'utilisateur n'est pas un admin alors il peut pas venir sur la page des notes
        if (!$user->isAdmin()) {
            return $this->redirectToRoute('app_home');
        }

        //On prend tout les notes qui ne sont pas modéré
        $ratings = $entityManager
            ->getRepository(Rating::class)
            ->findBy(['estModere' => false]);

        //On fait la pagination de tout les notes en attende d'être modéré
        $paginatorRatings = $paginator->paginate(
            $ratings,
            $request->query->getInt('page', 1),
            5
        );

        return $this->render('rating/index.html.twig', [
            'ratings' => $paginatorRatings,
        ]);
    }

    #[Route('/new/{idSerie}', name: 'app_rating_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        
        /** @var \App\Entity\User */
        $user = $this->getUser();

        //Si une personne n'est pas connecté ou que ce n'est pas un on lui demande de ce connecté
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        if (!$user->isAdmin()) {
            return $this->redirectToRoute('app_home');
        }

        # Récupère la série avec l'ID passé en paramètre de l'URL
        $series = $entityManager->getRepository(Series::class)->find($request->get('idSerie'));
        

        //Crée une nouvelle rating/note d'un utilisateur sur une série
        $rating = new Rating();

        

        # Associe la note à la série et à l'utilisateur
        $rating->setSeries($series);
        $rating->setUser($user);

        # Vérifie si un enregistrement de note existe déjà pour cette série et cet utilisateur
        $existingRating = $entityManager->getRepository(Rating::class)->findOneBy(['series' => $series, 'user' => $user]);

        # Création du formulaire pour la note
        $form = $this->createForm(RatingType::class, $rating);

        $form->handleRequest($request);

        if ($existingRating) {
            // On regarde si l'utilisateur a deja mise une note sur la séries concerné
            $form->get('value')->addError(new FormError("You've already given this series a rating and/or critic"));
        }

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
        /** @var App\Entity\User */
        $user = $this->getUser();

        //Si pas de compte connecté alors on lui dit de ce connecté
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        //Si l'utilisateur n'est pas un admin alors il peut pas venir sur la page des notes
        if (!$user->isAdmin()) {
            return $this->redirectToRoute('app_home');
        }

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
        /** @var App\Entity\User */
        $user = $this->getUser();

        //Si pas de compte connecté alors on lui dit de ce connecté
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        //Si l'utilisateur n'est pas un admin alors il peut pas venir sur la page des notes
        if (!$user->isAdmin()) {
            return $this->redirectToRoute('app_home');
        }

        if ($this->isCsrfTokenValid('delete' . $rating->getId(), $request->request->get('_token'))) {
            $entityManager->remove($rating);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_rating_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/generateCritic/{id}', name:'generateCritic', methods: ['POST'])]
    public function generateCritic(Request $request, EntityManagerInterface $em): Response
    {
        /** @var App\Entity\User */
        $user = $this->getUser();

        //Si pas de compte connecté alors on lui dit de ce connecté
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        //Si l'utilisateur n'est pas un admin alors il peut pas venir sur la page des notes
        if (!$user->isAdmin()) {
            return $this->redirectToRoute('app_home');
        }

        // Récupère le nombre de critique à générer
        $critic_count = $request->request->get('critic_count');

        //Récupère tout les séries existant
        $series = $em->getRepository(Series::class)->findAll();

        //On mélange les séries
        shuffle($series);

        // On prend le nombre d'utilisateur présent dans la table User
        $nbTotalUsers = $em->getRepository(User::class)->createQueryBuilder('u')
                ->select('count(u.id)')
                ->getQuery()
                ->getSingleScalarResult();

        // Sélectionne un utilisateur aléatoire
        $randomUser = $em->getRepository(User::class)->findBy([], null, 1, rand(0, $nbTotalUsers - 1));

        // Boucle pour générer un nombre d'utilisateurs
        for ($i = 0; $i < $critic_count; $i++) {
            $rating = new Rating();

            $rating->setUser($randomUser[0]);

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

    #[Route('/acceptCritic/{id}', name: 'app_rating_accept_critic', methods: ['GET'])]
    public function acceptCritic(Rating $rating, EntityManagerInterface $entityManager): Response
    {
        /** @var App\Entity\User */
        $user = $this->getUser();

        //On regarde que c'est bien un admin qui a les droit pour accepter une critique
        if (!$user || !$user->isAdmin()) {
            return $this->redirectToRoute('app_home');
        }

        # Met la variable admin à vrai pour que l'utilisateur soit un admin
        $rating->setEstModere(true);

        # Met à jour la base de données
        $entityManager->flush();

        # Redirige vers la page où il y a toute la liste des utilisateurs connecté
        return $this->redirectToRoute('app_rating_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/declineCritic/{id}', name: 'app_rating_decline_critic', methods: ['GET'])]
    public function declineCritics(Rating $rating, EntityManagerInterface $entityManager): Response
    {
        /** @var App\Entity\User */
        $user = $this->getUser();

        //On regarde que c'est bien un admin qui a les droit pour réfuser une critique
        if (!$user || !$user->isAdmin()) {
            return $this->redirectToRoute('app_home');
        }

        //On supprime la critique
        $entityManager->remove($rating);

        # Met à jour la base de données
        $entityManager->flush();

        # Redirige vers la page où il y a toute la liste des utilisateurs connecté
        return $this->redirectToRoute('app_rating_index', [], Response::HTTP_SEE_OTHER);
    }
}
