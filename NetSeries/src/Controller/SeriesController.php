<?php

namespace App\Controller;

use App\Entity\Episode;
use App\Entity\Season;
use App\Entity\Series;
use App\Form\SeriesType;
use App\Repository\EpisodeRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\Persistence\ManagerRegistry;

use Knp\Component\Pager\PaginatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

#[Route('/series')]
class SeriesController extends AbstractController
{
    private $episodeRepository;

    public function __construct(EpisodeRepository $episodeRepository, EntityManagerInterface $entityManager)
    {
        $this->episodeRepository = $episodeRepository;
    }



    #[Route('/', name: 'app_series_index', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $entityManager, PaginatorInterface $paginator): Response
    {
        // Récupère le repository des séries
        $appointmentsRepository = $entityManager->getRepository(Series::class);
        $allAppointmentsQuery = $appointmentsRepository->createQueryBuilder('search')
        ->orderBy('search.title', 'ASC')
        ->where('search.title LIKE :search')
        ->setParameter('search', '%' . $request->query->get('search') . '%')
        ->getQuery();
        $catvalue = $request->query->get('category');
        switch ($catvalue) {
            case 'title':
                $allAppointmentsQuery = $appointmentsRepository->createQueryBuilder('search')
                    ->orderBy('search.title', 'ASC')
                    ->where('search.title LIKE :search')
                    ->setParameter('search', '%' . $request->query->get('search') . '%')
                    ->getQuery();
                break;
            case 'genre':
                $allAppointmentsQuery = $appointmentsRepository->createQueryBuilder('search')
                    ->orderBy('search.title', 'ASC')
                    ->join('search.genre', 'genre')
                    ->where('search.genre LIKE :search')
                    ->setParameter('search', '%' . $request->query->get('search') . '%')
                    ->getQuery();
                break;
        }

        // Crée une requête pour sélectionner toutes les séries



        // Pagination des résultats (5 séries par pages maximum)
        $appointments = $paginator->paginate(
            $allAppointmentsQuery,
            $request->query->getInt('page', 1),
            12
        );

        return $this->render('series/index.html.twig', [
            'series' => $appointments,
        ]);
    }


    #[Route('/new', name: 'app_series_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $series = new Series();
        $form = $this->createForm(SeriesType::class, $series);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($series);
            $entityManager->flush();

            return $this->redirectToRoute('app_series_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('series/new.html.twig', [
            'series' => $series,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_series_show', methods: ['GET'])]
    public function show(Series $series): Response
    {

        $seasons = $series->getSeasons();
        $episodesBySeason = [];
        foreach ($seasons as $season) {
            $episodesBySeason[$season->getNumber()] = $this->episodeRepository->findBySeason($season->getId());
        }
        return $this->render('series/show.html.twig', [
            'series' => $series,
            'episodesBySeason' => $episodesBySeason,
        ]);
    }

    #[Route('/poster/{id}', name: 'app_poster_show', methods: ['GET', 'POST'])]
    public function shows(Series $series): Response
    {
        return new Response(stream_get_contents($series->getPoster()), 200, array('Content-Type' => 'image/png'));
    }

    #[Route('/{id}/edit', name: 'app_series_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Series $series, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(SeriesType::class, $series);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_series_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('series/edit.html.twig', [
            'series' => $series,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_series_delete', methods: ['POST'])]
    public function delete(Request $request, Series $series, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $series->getId(), $request->request->get('_token'))) {
            $entityManager->remove($series);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_series_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/view/{id1}/{id2}', name: 'app_episode_view', methods: ['GET', 'POST'])]
    public function view(EntityManagerInterface $entityManager, Request $request): Response
    {
        # Récupère l'utilisateur connecté courant
        /** @var \App\Entity\User */
        $user = $this->getUser();

        # Récupère l'épisode avec l'ID passé en paramètre de l'URL
        /** @var \App\Entity\Episode */
        $episodes = $entityManager->getRepository(Episode::class)->find($request->get('id1'));
        # si l'episode précédent n'est pas vu, on l'ajoute à la liste des épisodes vus jusqu'au premier épisode de la saison
        # get all seasons before current season

        $episodes = $entityManager->getRepository(Episode::class)->findBy(['season' => $episodes->getSeason(), 'number' => range(1, $episodes->getNumber())]);
        foreach ($episodes as $episode) {
            $user->addEpisode($episode);
        }
        # get 1 epiosde from the episodes array
        $exepisode = $episodes[0];
        $seasons = $entityManager->getRepository(Season::class)->findBy(['series' => $request->get('id2'), 'number' => range(1, $exepisode->getSeason()->getNumber() - 1)]);
        foreach ($seasons as $season) {

            $episodes = $entityManager->getRepository(Episode::class)->findBy(['season' => $season]);
            foreach ($episodes as $episode) {
                $user->addEpisode($episode);
            }
        }


        $series = $entityManager->getRepository(Series::class)->find($request->get('id2'));

        # Met à jour la base de données
        $entityManager->flush();

        # Redirige vers la page où il y a l'information de la série choisis
        return $this->redirectToRoute('app_series_show', ['id' => $series->getId()],  Response::HTTP_SEE_OTHER);
    }

    #[Route('/remove-view/{id1}/{id2}', name: 'app_episode_remove_view', methods: ['GET', 'POST'])]
    public function removeView(EntityManagerInterface $entityManager, Request $request): Response
    {
        # Récupère l'utilisateur connecté courant
        /** @var \App\Entity\User */
        $user = $this->getUser();

        # Récupère l'épisode avec l'ID passé en paramètre de l'URL
        /** @var \App\Entity\Episode */
        $episodes = $entityManager->getRepository(Episode::class)->find($request->get('id1'));

        # Supprime l'épisode de la liste des épisodes vus par l'utilisateur
        $user->removeEpisode($episodes);

        # Récupère la série avec l'ID passé en paramètre de l'URL
        $series = $entityManager->getRepository(Series::class)->find($request->get('id2'));

        # Met à jour la base de données
        $entityManager->flush();

        # Redirige vers la page où il y a l'information de la série choisis
        return $this->redirectToRoute('app_series_show', ['id' => $series->getId()],  Response::HTTP_SEE_OTHER);
    }

    #[Route('/suivre/{id}', name: 'follow_series', methods: ['GET', 'POST'])]
    public function follow(EntityManagerInterface $entityManager, Request $request): Response
    {
        # Récupère l'utilisateur connecté courant
        /** @var \App\Entity\User */
        $user = $this->getUser();

        # Récupère la série avec l'ID passé en paramètre de l'URL
        $series = $entityManager->getRepository(Series::class)->find($request->get('id'));

        # Ajoute la série à la liste des séries suivies par l'utilisateur
        $user->addSeries($series);

        # Met à jour la base de données
        $entityManager->flush();

        # Redirige vers la page où il y a toute les séries
        return $this->redirectToRoute('app_series_index');
    }

    #[Route('/unfollow/{id}', name: 'unfollow_series', methods: ['GET', 'POST'])]
    public function unfollow(EntityManagerInterface $entityManager, Request $request): Response
    {
        # Récupère l'utilisateur connecté courant
        /** @var \App\Entity\User */
        $user = $this->getUser();

        # Récupère la série avec l'ID passé en paramètre de l'URL
        $series = $entityManager->getRepository(Series::class)->find($request->get('id'));

        # Supprime la série de la liste des séries suivies par l'utilisateur
        $user->removeSeries($series);

        # Met à jour la base de données
        $entityManager->flush();

        # Redirige vers la page où il y a toute les séries suivi
        return $this->redirectToRoute('app_followed_series');
    }

    #[Route('/search',  name: 'app_series_search', methods: ['GET'])]
    public function search(Request $request, PaginatorInterface $paginator, ManagerRegistry $doctrine)
    {



        return $this->render('series/index.html.twig', [
            'series' => $series,
        ]);
    }
}
