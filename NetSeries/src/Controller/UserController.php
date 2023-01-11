<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Rating;
use App\Entity\Series;
use App\Form\UserType;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Entity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


use Knp\Component\Pager\PaginatorInterface;

#[Route('/user')]
class UserController extends AbstractController
{
    #[Route('/', name: 'app_user_index', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $entityManager, PaginatorInterface $paginator): Response
    {
        // if the user is logged in and is admin accessed by the isAdmin() method of User.php, show the user list
        /** @var \App\Entity\User */
        $user = $this->getUser();

        if (!$user){
            return $this->redirectToRoute('app_login');
        }

        // Récupère le repository des séries
        $appointmentsRepository = $entityManager->getRepository(User::class);

        // Crée une requête pour sélectionner toutes les séries
        $allAppointmentsQuery = $appointmentsRepository->createQueryBuilder('p')
            ->getQuery();

        $allAppointmentsQuery = $appointmentsRepository->createQueryBuilder('search')
            ->orderBy('search.email', 'ASC')
            ->where('search.email LIKE :search')
            ->setParameter('search', '%' . $request->query->get('search') . '%')
            ->getQuery();

        // Pagination des résultats (5 séries par pages maximum)
        $appointments = $paginator->paginate(
            $allAppointmentsQuery,
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('user/index.html.twig', [
            'users' => $appointments,
        ]);
        
        // if the user is logged in but is not admin, redirect to the homepage
        //return $this->redirectToRoute("app_series_index");
    }

    #[Route('/new', name: 'app_user_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($user);
            $entityManager->flush();

            return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('user/new.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_user_show', methods: ['GET'])]
    public function show(User $user, EntityManagerInterface $entityManager, Request $request, PaginatorInterface $paginator): Response
    {


        $appointmentsQuerySeriesFollowed = $user->getSeries();

        $user = $entityManager->getRepository(User::class)->find($request->get('id'));
    


        $appointmentsQueryRating = $user->getRatings();

        /** @var \App\Entity\Paginator */
        $appointmentsRatings = $paginator->paginate(
            $appointmentsQueryRating,
            $request->query->getInt('ratings_page', 1),
            2,
            ['pageParameterName' => 'ratings_page']
        );

        $appointmentsSeriesFollowed = $paginator->paginate(
            $appointmentsQuerySeriesFollowed,
            $request->query->getInt('series_page', 1),
            2,
            ['pageParameterName' => 'series_page']
        );


        

            

        return $this->render('user/show.html.twig', [
            'user' => $user,
            'ratings' => $appointmentsRatings,
            'seriesFollowed' => $appointmentsSeriesFollowed
        ]);
    }

    #[Route('/{id}/edit', name: 'app_user_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('user/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_user_delete', methods: ['POST'])]
    public function delete(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            $entityManager->remove($user);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/user/promote/{id}', name: 'app_user_promote', methods: ['GET'])]
    public function promote(User $user, EntityManagerInterface $entityManager): Response
    {
        # Met la variable admin à vrai pour que l'utilisateur soit un admin
        $user->setAdmin(true);

        # Met à jour la base de données
        $entityManager->flush();
        
        # Redirige vers la page où il y a toute la liste des utilisateurs connecté
        return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/user/demote/{id}', name: 'app_user_demote', methods: ['GET'])]
    public function demote(User $user, EntityManagerInterface $entityManager): Response
    {
        # Met la variable admin à faux pour que l'utilisateur soit plus un admin
        $user->setAdmin(false);

        # Met à jour la base de données
        $entityManager->flush();

        # Redirige vers la page où il y a toute la liste des utilisateurs connecté
        return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/user/followed/{id}', name: 'app_user_followedSeriesById', methods: ['GET'])]
    public function followedSerie(User $user, EntityManagerInterface $entityManager, Request $request, PaginatorInterface $paginator): Response
    {
        // if the user is not logged in, redirect to the login page
        $user = $entityManager->getRepository(User::class)->find($request->get('id'));

        $seriesFollowed = $user->getSeries();

        $seriesFollowedPaginated = $paginator->paginate(
            $seriesFollowed,
            $request->query->getInt('page', 1), /*page number*/
            7 /*limit per page*/
        );
        
        return $this->render('user/followedSeriesForUser.html.twig', [
            'series' => $seriesFollowedPaginated,
        ]);
    }
    
}
