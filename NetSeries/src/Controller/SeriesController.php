<?php

namespace App\Controller;

use App\Entity\Episode;
use App\Entity\Season;
use App\Entity\Series;
use App\Entity\Rating;
use App\Entity\User;
use App\Form\SeriesType;
use App\Repository\EpisodeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Entity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use Knp\Component\Pager\PaginatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Doctrine\ORM\Query\ResultSetMapping;

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
        // Vérifie si le paramètre de requête "order" est défini sur "ASC" ou "DESC"
        if ($request->query->get('order') == 'ASC') {
            $AscOrDesc = "ASC";
        } else {
            $AscOrDesc = "DESC";
        }

        // On récupère les séries d'après les trier de l'utilisateur
        $qb = $entityManager->getRepository(Series::class)->createQueryBuilder('s');

        // Vérifie si il y a une recherche (barre de recherche) qui a était donnée
        if ($search = $request->query->get('search')) {
            $qb->andWhere('s.title LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        // Vérifie si il y a un genre qui a était donnée
        if ($genreName = $request->query->get('genres')) {
            $qb->leftJoin('s.genre', 'g')
                ->andWhere('g.name = :genres')
                ->setParameter('genres', $genreName);
        }

        // Vérifie si il y a une date qui a était donnée
        if ($date = $request->query->get('date')) {
            $qb->andWhere('s.yearStart = :date')
                ->setParameter('date', $date);
        }

        // Vérifie si il y a un acteur qui a était donnée
        if ($actor = $request->query->get('actor')) {
            $qb->leftJoin('s.actor', 'a')
                ->andWhere('a.name LIKE :actor')
                ->setParameter('actor', '%' . $actor . '%');
        }

        // Vérifie si il y a une note minimale qui a était donnée
        if ($minnote = $request->query->get('minnote')) {
            $subquery = $entityManager->getRepository(Rating::class)->createQueryBuilder('r')
                ->select('AVG(r.value/2)')
                ->where('r.series = s')
                ->getDQL();

            $qb->andWhere(sprintf('(%s) >= :minnote', $subquery))
                ->setParameter('minnote', $minnote);
        }

        // Vérifie si il y a une note maximale qui a était donnée
        if ($maxnote = $request->query->get('maxnote')) {
            $subquery = $entityManager->getRepository(Rating::class)->createQueryBuilder('m')
                ->select('AVG(m.value/2)')
                ->where('m.series = s')
                ->getDQL();

            $qb->andWhere(sprintf('(%s) <= :maxnote', $subquery))
                ->setParameter('maxnote', $maxnote);
        }

        //On trie les séries par l'ordre choisis par l'utilisateur
        $qb->orderBy('s.title', $AscOrDesc);

        // Pagination des séries sur la requete faite
        $series = $paginator->paginate(
            $qb->getQuery(),
            $request->query->getInt('page', 1),
            12
        );



        // Récupération de tous les genres
        $genres = $entityManager->getRepository(\App\Entity\Genre::class)->findAll();

        


        return $this->render('series/index.html.twig', [
            'series' => $series,
            'genres' => $genres,
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
    public function show(Series $series, EntityManagerInterface $em, Request $request, PaginatorInterface $paginator): Response
    {
        // Récupère tout les saisons d'une série
        $seasons = $series->getSeasons();
        $episodesBySeason = [];

        // Récupère l'utilisateur courant
        $user = $this->getUser(); // Récupérer l'utilisateur courant

        // Récupère la série via l'id passé en paramètre de la requête
        $series = $em->getRepository(Series::class)->find($request->get('id'));

        // Récupère la note de l'utilisateur courant pour cette série
        $rating = $em->getRepository(Rating::class)->findOneBy(['user' => $user, 'series' => $series]);

        // Récupère toutes les notes pour cette série
        $ratings = $em->getRepository(Rating::class)->findOneBy(['series' => $rating]);

        // Vérifie si l'utilisateur courant a noté cette série
        if ($rating) {
            $userHasRated = true;
        } else {
            $userHasRated = false;
        }

        // Récupère tous les épisodes de chaque saison
        foreach ($seasons as $season) {
            $episodesBySeason[$season->getNumber()] = $this->episodeRepository->findBySeason($season->getId());
        }

        // Compte le nombre de notes entre 0 et 1, entre 1 et 2, entre 2 et 3, entre 3 et 4, entre 4 et 5
        $ratingsBetween0And1 = $series->getRatings()->filter(function (Rating $rating) {
            $halfValue = $rating->getValue() / 2;
            return $halfValue >= 0 && $halfValue < 1;
        })->count();

        $ratingsBetween1And2 = $series->getRatings()->filter(function (Rating $rating) {
            $halfValue = $rating->getValue() / 2;
            return $halfValue  >= 1 && $halfValue < 2;
        })->count();

        $ratingsBetween2And3 = $series->getRatings()->filter(function (Rating $rating) {
            $halfValue = $rating->getValue() / 2;
            return $halfValue  >= 2 && $halfValue < 3;
        })->count();

        $ratingsBetween3And4 = $series->getRatings()->filter(function (Rating $rating) {
            $halfValue = $rating->getValue() / 2;
            return $halfValue  >= 3 && $halfValue < 4;
        })->count();

        $ratingsBetween4And5 = $series->getRatings()->filter(function (Rating $rating) {
            $halfValue = $rating->getValue() / 2;
            return $halfValue  >= 4 && $halfValue <= 5;
        })->count();

        // Retourne les détails de la série, les épisodes par saison, si l'utilisateur a noté la série, et les statistiques de notes
        return $this->render('series/show.html.twig', [
            'series' => $series,
            'episodesBySeason' => $episodesBySeason,
            'userHasRated' => $userHasRated,
            'ratings' => $ratings,
            'ratingsBetween0And1' => $ratingsBetween0And1,
            'ratingsBetween1And2' => $ratingsBetween1And2,
            'ratingsBetween2And3' => $ratingsBetween2And3,
            'ratingsBetween3And4' => $ratingsBetween3And4,
            'ratingsBetween4And5' => $ratingsBetween4And5,
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
        if ($exepisode->getSeason()->getNumber() > 1) {
            $seasons = $entityManager->getRepository(Season::class)->findBy(['series' => $request->get('id2'), 'number' => range(1, $exepisode->getSeason()->getNumber() - 1)]);
            foreach ($seasons as $season) {

                $episodes = $entityManager->getRepository(Episode::class)->findBy(['season' => $season]);
                foreach ($episodes as $episode) {
                    $user->addEpisode($episode);
                }
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

    #[Route('/viewseason/{id1}/{id2}', name: 'app_season_view', methods: ['GET', 'POST'])]
    public function viewSeason(EntityManagerInterface $entityManager, Request $request): Response
    {
        # Récupère l'utilisateur connecté courant
        /** @var \App\Entity\User */
        $user = $this->getUser();



        # Récupère la série avec l'ID passé en paramètre de l'URL
        $series = $entityManager->getRepository(Series::class)->find($request->get('id1'));

        # Récupère la saison avec le numéro de saison de passé en paramètre de l'URL
        /** @var \App\Entity\Season */
        $season = $entityManager->getRepository(Season::class)->findOneBy(['series' => $request->get('id1'), 'number' => $request->get('id2')]);

        # Récupère tous les épisodes de la saison
        /** @var \App\Entity\Episode */
        $episodes = $entityManager->getRepository(Episode::class)->findBy(['season' => $season]);

        # Ajoute tous les épisodes de la saison à la liste des épisodes vus par l'utilisateur
        foreach ($episodes as $episode) {
            $user->addEpisode($episode);
        }

        # Met à jour la base de données
        $entityManager->flush();

        # Redirige vers la page où il y a l'information de la série choisis
        return $this->redirectToRoute('app_series_show', ['id' => $series->getId()],  Response::HTTP_SEE_OTHER);
    }

    #[Route('/remove-viewseason/{id1}/{id2}', name: 'app_season_remove_view', methods: ['GET', 'POST'])]
    public function removeViewSeason(EntityManagerInterface $entityManager, Request $request): Response
    {
        # Récupère l'utilisateur connecté courant
        /** @var \App\Entity\User */
        $user = $this->getUser();

        # Récupère la série avec l'ID passé en paramètre de l'URL
        $series = $entityManager->getRepository(Series::class)->find($request->get('id1'));

        # Récupère la saison avec le numéro de saison de passé en paramètre de l'URL
        /** @var \App\Entity\Season */
        $season = $entityManager->getRepository(Season::class)->findOneBy(['series' => $request->get('id1'), 'number' => $request->get('id2')]);

        # Récupère tous les épisodes de la saison
        /** @var \App\Entity\Episode */
        $episodes = $entityManager->getRepository(Episode::class)->findBy(['season' => $season]);

        # Supprime tous les épisodes de la saison de la liste des épisodes vus par l'utilisateur
        foreach ($episodes as $episode) {
            $user->removeEpisode($episode);
        }

        # Met à jour la base de données
        $entityManager->flush();

        # Redirige vers la page où il y a l'information de la série choisis
        return $this->redirectToRoute('app_series_show', ['id' => $series->getId()],  Response::HTTP_SEE_OTHER);
    }

        
}
