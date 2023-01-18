<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Country;
use App\Entity\Rating;
use App\Entity\Series;
use App\Form\UserType;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Entity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Faker\Factory as Faker;


use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasher;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;

#[Route('/user')]
class UserController extends AbstractController
{

    /** @var EntityManagerInterface */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/', name: 'app_user_index', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $entityManager, PaginatorInterface $paginator): Response
    {
        // if the user is logged in and is admin accessed by the isAdmin() method of User.php, show the user list
        /** @var \App\Entity\User */
        $user = $this->getUser();

        //Si une personne n'est pas connecté on lui demande de ce connecté
        if (!$user){
            return $this->redirectToRoute('app_login');
        }

        // Récupère le repository des séries
        $appointmentsRepository = $entityManager->getRepository(User::class);

        // Crée une requête pour sélectionner toutes les séries
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

        //On récupère tout les séries existant
        $series = $entityManager->getRepository(Series::class)->findAll();

        return $this->render('user/index.html.twig', [
            'users' => $appointments,
            'series' => $series,
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
        /** @var \App\Entity\User */
        $userActual = $this->getUser();

        //Si une personne n'est pas connecté on lui demande de ce connecté
        if (!$userActual){
            return $this->redirectToRoute('app_login');
        }

        // Récupère les séries suivies par l'utilisateur
        $appointmentsQuerySeriesFollowed = $user->getSeries();

        // Récupère l'utilisateur via son ID passé à l'URL
        $user = $entityManager->getRepository(User::class)->find($request->get('id'));
    
        // Récupère les notes de l'utilisateur
        $appointmentsQueryRating = $user->getRatings();

        // Pagination des notes et critique de l'utilisateur
        /** @var \App\Entity\Paginator */
        $appointmentsRatings = $paginator->paginate(
            $appointmentsQueryRating,
            $request->query->getInt('ratings_page', 1),
            2,
            ['pageParameterName' => 'ratings_page']
        );

        // Pagination des séries suivies par l'utilisateur
        $appointmentsSeriesFollowed = $paginator->paginate(
            $appointmentsQuerySeriesFollowed,
            $request->query->getInt('series_page', 1),
            2,
            ['pageParameterName' => 'series_page']
        );

        // Retourne les détails de l'utilisateur, les notes qu'il a données et les séries qu'il suit
        return $this->render('user/show.html.twig', [
            'user' => $user,
            'ratings' => $appointmentsRatings,
            'seriesFollowed' => $appointmentsSeriesFollowed
        ]);
    }

    #[Route('/{id}/edit', name: 'app_user_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user, EntityManagerInterface $entityManager, UserPasswordHasherInterface $userPasswordHasher): Response
    {
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->get('plainPassword')->getData() != null) {
                $user->setPassword($userPasswordHasher->hashPassword($user, $form->get('plainPassword')->getData()));
            }
            
            $entityManager->flush();
            return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('user/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/profil', name: 'app_user_profil', methods: ['GET', 'POST'])]
    public function profil(Request $request, User $user, EntityManagerInterface $entityManager, UserPasswordHasherInterface $userPasswordHasher): Response
    {
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->get('plainPassword')->getData() != null) {
                $user->setPassword($userPasswordHasher->hashPassword($user, $form->get('plainPassword')->getData()));
            }
            
            $entityManager->flush();
            return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('user/profil.html.twig', [
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

        return $this->redirectToRoute('app_home', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/promote/{id}', name: 'app_user_promote', methods: ['GET'])]
    public function promote(User $user, EntityManagerInterface $entityManager): Response
    {
        /** @var \App\Entity\User */
        $userActual = $this->getUser();

        //Si une personne n'est pas connecté ou qu'il est suspendu on lui demande de ce connecté
        if (!$userActual || $userActual->isEstSuspendu()){
            return $this->redirectToRoute('app_login');
        }

        # Met la variable admin à vrai pour que l'utilisateur soit un admin
        $user->setAdmin(true);

        # Met à jour la base de données
        $entityManager->flush();
        
        # Redirige vers la page où il y a toute la liste des utilisateurs connecté
        return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/demote/{id}', name: 'app_user_demote', methods: ['GET'])]
    public function demote(User $user, EntityManagerInterface $entityManager): Response
    {
        /** @var \App\Entity\User */
        $userActual = $this->getUser();

        //Si une personne n'est pas connecté ou qu'il est suspendu on lui demande de ce connecté
        if (!$userActual || $userActual->isEstSuspendu()){
            return $this->redirectToRoute('app_login');
        }

        # Met la variable admin à faux pour que l'utilisateur soit plus un admin
        $user->setAdmin(false);

        # Met à jour la base de données
        $entityManager->flush();

        # Redirige vers la page où il y a toute la liste des utilisateurs connecté
        return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/followed/{id}', name: 'app_user_followedSeriesById', methods: ['GET'])]
    public function followedSerie(User $user, EntityManagerInterface $entityManager, Request $request, PaginatorInterface $paginator): Response
    {
        /** @var \App\Entity\User */
        $userActual = $this->getUser();

         //Si une personne n'est pas connecté on lui demande de ce connecté
        if (!$userActual){
            return $this->redirectToRoute('app_login');
        }

        // if the user is not logged in, redirect to the login page
        $user = $entityManager->getRepository(User::class)->find($request->get('id'));

        //On récupère tout les séries qui suit
        $seriesFollowed = $user->getSeries();

        $seriesFollowedPaginated = $paginator->paginate(
            $seriesFollowed,
            $request->query->getInt('page', 1), /*page number*/
            /*7 limit per page*/
        );
        
        return $this->render('user/followedSeriesForUser.html.twig', [
            'series' => $seriesFollowedPaginated,
        ]);
    }

    #[Route('/generate/{id}', name:'generateUser', methods: ['POST'])]
    public function generateAndInsertUsers(User $user, Request $request) : Response
    {
        /** @var \App\Entity\User */
        $userActual = $this->getUser();

         //Si une personne n'est pas connecté ou qu'il est suspendu on lui demande de ce connecté
        if (!$userActual || $userActual->isEstSuspendu()){
            return $this->redirectToRoute('app_login');
        }

        // Récupère le nombre d'utilisateurs à générer
        $userCount = $request->request->get('user_count');

        // Initialise Faker pour pouvoir faire des comptes cohérent
        $faker = Faker::create();

        // Taille du lot pour l'insertion en base de données pour une meilleur optimisation
        $batchSize = 20;

        // Récupère tous les pays
        $countries = $this->entityManager->getRepository(Country::class)->findAll();
        
        // Mélange les pays
        shuffle($countries);

        // Sélectionne un pays aléatoire
        $randomCountry = array_pop($countries);

        // Un même mot de passe pour tous les utilisateurs
        $passwordAllUser = password_hash($faker->password(), PASSWORD_DEFAULT);

        // Boucle pour générer un nombre d'utilisateurs
        for ($i = 0; $i < $userCount; $i++) {
            $user = new User();
            // Génère un nom d'utilisateur unique
            $user->setName($faker->unique()->userName);
            // Génère un email unique
            $user->setEmail($faker->unique()->email);
            // Ajoute le mot de passe à l'utilisateur
            $user->setPassword($passwordAllUser);
            // Ajoute le pays aléatoire à l'utilisateur
            $user->setCountry($randomCountry);
            if (($i % $batchSize) === 0) {
                $this->entityManager->flush();
                $this->entityManager->clear();
            }
        }

        // mise à jour de la base de données
        $this->entityManager->flush();
        $this->entityManager->clear();

        // redirige vers la page d'index des utilisateurs
        return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/ban/{id}', name: 'app_user_ban_user', methods: ['GET'])]
    public function ban(User $user, EntityManagerInterface $entityManager): Response
    {
        /** @var \App\Entity\User */
        $userActual = $this->getUser();

         //Si une personne n'est pas connecté ou qu'il est suspendu on lui demande de ce connecté
        if (!$userActual || $userActual->isEstSuspendu()){
            return $this->redirectToRoute('app_login');
        }

        # Met la variable suspendu à vrai pour que l'utilisateur soit banni
        $user->setEstSuspendu(true);

        # Met à jour la base de données
        $entityManager->flush();
        
        # Redirige vers la page où il y a toute la liste des utilisateurs connecté
        return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
    }

    

    
    
    #[Route('/usersFollowed/{id}', name: 'app_user_usersFollowed', methods: ['GET'])]
    public function usersFollowed(User $user, EntityManagerInterface $entityManager, Request $request, PaginatorInterface $paginator): Response
    {
        /** @var App\Entity\User */
        $userCurrant = $this->getUser();

         //Si une personne n'est pas connecté on lui demande de ce connecté
        if (!$userCurrant) {
            return $this->redirectToRoute('app_login', [], Response::HTTP_SEE_OTHER);
        }

        //On ajoute le nouveur utilisateur qui suit
        $userCurrant->addFollowUser($user);

        # Met à jour la base de données
        $entityManager->flush();

        
        # Redirige vers la page où il y a toute la liste des utilisateurs connecté
        return $this->redirectToRoute('app_user_show', [
            'id' => $user->getId(),
        ], Response::HTTP_SEE_OTHER);
    }

    #[Route('/usersUnfollowed/{id}', name: 'app_user_usersUnfollowed', methods: ['GET'])]
    public function usersUnfollowed(User $user, EntityManagerInterface $entityManager, Request $request, PaginatorInterface $paginator): Response
    {
        /** @var App\Entity\User */
        $userCurrant = $this->getUser();

         //Si une personne n'est pas connecté on lui demande de ce connecté
        if (!$userCurrant) {
            return $this->redirectToRoute('app_login', [], Response::HTTP_SEE_OTHER);
        }

        //On enlève l'utilisateur choisi de ces abonnements
        $userCurrant->removeFollowUser($user);

        # Met à jour la base de données
        $entityManager->flush();

        # Redirige vers la page où il y a toute la liste des utilisateurs connecté
        return $this->redirectToRoute('app_user_show', [
            'id' => $user->getId(),
        ], Response::HTTP_SEE_OTHER);
    }

    #[Route('/listUsersFollowed/{id}', name: 'app_user_listUsersFollowed', methods: ['GET'])]
    public function listUsersFollowed(User $user, EntityManagerInterface $entityManager, Request $request, PaginatorInterface $paginator): Response
    {
         //Si une personne n'est pas connecté on lui demande de ce connecté
        if (!$user) {
            return $this->redirectToRoute('app_login', [], Response::HTTP_SEE_OTHER);
        }

        # Met la variable admin à vrai pour que l'utilisateur soit un admin
        $allUsers =$user->getFollowUser();

        # Redirige vers la page où il y a toute la liste des utilisateurs connecté
        return $this->render('user/follow.html.twig', [
            'users' => $allUsers,
        ]);
    }
}
