<?php

namespace App\Controller;

use App\Entity\Episode;
use App\Entity\Season;
use App\Entity\Series;
use App\Entity\Rating;
use App\Entity\User;
use App\Entity\Genre;
use App\Entity\Actor;
use App\Entity\ExternalRating;
use App\Entity\ExternalRatingSource;
use App\Form\SeriesType;
use App\Repository\EpisodeRepository;
use Doctrine\ORM\EntityManager;
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

        // Vérifie si il y a une date minimum qui a était donnée
        if ($date = $request->query->get('dateMin')) {
            $qb->andWhere('s.yearStart >= :dateMin')
                ->setParameter('dateMin', $date);
        }

        // Vérifie si il y a une date maximum qui a était donnée
        if ($date = $request->query->get('dateMax')) {
            $qb->andWhere('s.yearStart <= :dateMax')
                ->setParameter('dateMax', $date);
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
                ->leftJoin('r.user', 'us')
                ->where('r.series = s AND r.estModere = true')
                ->andWhere('us.estSuspendu = false')
                ->getDQL();

            $qb->andWhere(sprintf('(%s) >= :minnote', $subquery))
                ->setParameter('minnote', $minnote);
        }

        // Vérifie si il y a une note maximale qui a était donnée
        if ($maxnote = $request->query->get('maxnote')) {
            $subquery = $entityManager->getRepository(Rating::class)->createQueryBuilder('m')
                ->select('AVG(m.value/2)')
                ->leftJoin('m.user', 'u')
                ->where('m.series = s AND m.estModere = true')
                ->andWhere('u.estSuspendu = false')
                ->getDQL();

            $qb->andWhere(sprintf('(%s) <= :maxnote', $subquery))
                ->setParameter('maxnote', $maxnote);
        }



        if ($request->query->get('order') == 'noteCroissant') {
            $qb->leftJoin('s.ratings', 'c')
                ->leftJoin('c.user', 'us')
                ->addSelect('AVG(c.value/2) as HIDDEN avg_value')
                ->where('c.estModere = true')
                ->andWhere('us.estSuspendu = false')
                ->groupBy('s.id')->orderBy('avg_value', 'ASC');
        } else if ($request->query->get('order') == 'noteDecroissant') {
            $qb->leftJoin('s.ratings', 'd')
                ->leftJoin('d.user', 'u')
                ->addSelect('AVG(d.value/2) as HIDDEN avg_value')
                ->where('d.estModere = true')
                ->andWhere('u.estSuspendu = false')
                ->groupBy('s.id')->orderBy('avg_value', 'DESC');
        } else if ($request->query->get('order') == 'DESC') {
            $qb->orderBy('s.title', "DESC");
        } else {
            $qb->orderBy('s.title', "ASC");
        }

        // Pagination des séries sur la requete faite
        $series = $paginator->paginate(
            $qb,
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


    #[Route('/search', name: 'app_series_search', methods: ['GET', 'POST'])]
    public function search(Request $request, EntityManagerInterface $entityManager): Response
    {
        # check if the form is submitted
        $data = [];

        $youtubeApiKey = 'AIzaSyAUM21-ZFmzqdAoK1Yyr9BNDrE93ikxaBE';

        if ($request->query->get('title')) {
            $title = $request->query->get('title');
            $title = str_replace(' ', '+', $title);
            $url = "http://www.omdbapi.com/?t=" . $title . "&apikey=7a8be84b&type=series";


            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
            curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:11.0) Gecko/20100101 Firefox/11.0');
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            $result = curl_exec($curl);
            curl_close($curl);
            $data = json_decode($result, true);
            if ($data['Response'] == 'False') {
                $this->addFlash('danger', 'Aucune série trouvée');
                return $this->render('series/search.html.twig', [
                    'data' => $data,
                ]);
            } else {
                $this->addFlash('success', 'Série trouvée');
                $totalSeasons = $data['totalSeasons'];
                if ($totalSeasons != "N/A") {
                    $episodesData = array();
                    for ($i = 1; $i <= $totalSeasons; $i++) {
                        $urlEpisode = "http://www.omdbapi.com/?i=" . $data['imdbID'] . "&apikey=7a8be84b&Season=" . $i;
                        $curl = curl_init();
                        curl_setopt($curl, CURLOPT_URL, $urlEpisode);
                        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
                        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
                        curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:11.0) Gecko/20100101 Firefox/11.0');
                        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
                        $result = curl_exec($curl);
                        curl_close($curl);

                        $dataEpisode = json_decode($result, true);
                        $episodesData[] = $dataEpisode;
                    }
                    $titlefull = $data['Title'];
                    $titlefull = str_replace(' ', '+', $titlefull);
                    $searchUrl = "https://www.googleapis.com/youtube/v3/search?part=snippet&q=" . $titlefull . "+trailer&key=" . $youtubeApiKey . "&maxResults=1&chart=mostPopular";
                    $curl = curl_init();
                    curl_setopt($curl, CURLOPT_URL, $searchUrl);
                    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
                    curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
                    curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:11.0) Gecko/20100101 Firefox/11.0');
                    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
                    $result = curl_exec($curl);
                    curl_close($curl);
                    $searchResults = json_decode($result, true);
                    foreach ($searchResults['items'] as $searchResult) {
                        if ($searchResult['id']['kind'] === 'youtube#video') {
                            $videoId = $searchResult['id']['videoId'];
                            $videoUrl = "https://www.youtube.com/watch?v=$videoId";
                        }
                    }

                    return $this->render('series/search.html.twig', [
                        'data' => $data,
                        'episodesData' => $episodesData,
                        'videoUrl' => $videoUrl,
                    ]);
                }
                $this->addFlash('danger', 'Aucune série trouvée');
                $titlefull = $data['Title'];
                $titlefull = str_replace(' ', '+', $titlefull);
                $searchUrl = "https://www.googleapis.com/youtube/v3/search?part=snippet&q=" . $titlefull . "+trailer&key=" . $youtubeApiKey . "&maxResults=1&chart=mostPopular";
                $curl = curl_init();
                curl_setopt($curl, CURLOPT_URL, $searchUrl);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
                curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:11.0) Gecko/20100101 Firefox/11.0');
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
                $result = curl_exec($curl);
                curl_close($curl);
                $searchResults = json_decode($result, true);
                foreach ($searchResults['items'] as $searchResult) {
                    if ($searchResult['id']['kind'] === 'youtube#video') {
                        $videoId = $searchResult['id']['videoId'];
                        $videoUrl = "https://www.youtube.com/watch?v=$videoId";
                    }
                }
                return $this->render('series/search.html.twig', [
                    'data' => $data,
                    'videoUrl' => $videoUrl,
                ]);
            }
        }
        $this->addFlash('danger', 'Aucune série trouvée');
        return $this->render('series/search.html.twig', [
            'data' => $data,
        ]);
    }

    #[Route('/newserie', name: 'app_series_import', methods: ['GET', 'POST'])]
    public function newserie(Request $request, EntityManagerInterface $entityManager): Response
    {
        $series = new Series();
        $title = $request->request->get('title');
        $plot = $request->request->get('plot');
        $genres = $request->request->get('genre');
        $director = $request->request->get('director');
        $actors = $request->request->get('actors');
        $awards = $request->request->get('awards');
        $yearStart = $request->request->get('yearStart');
        $yearEnd = $request->request->get('yearEnd');
        $imdbRating = $request->request->get('imdbRating');
        $poster = $request->request->get('poster');
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $poster);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:11.0) Gecko/20100101 Firefox/11.0');
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        $data = curl_exec($curl);
        curl_close($curl);

        $trailer = $request->request->get('trailer');
        $imdbID = $request->request->get('imdbID');

        $genreNames = explode(',', $genres);
        foreach ($genreNames as $genreName) {
            $genreName = trim($genreName);
            /** @var \App\Entity\Genre */
            $genre = $entityManager->getRepository(Genre::class)->findOneBy(['name' => $genreName]);
            if ($genre) {
                $series->addGenre($genre);
            }
        }
        $actorsNames = explode(',', $actors);
        foreach ($actorsNames as $actorName) {
            $actorName = trim($actorName);
            /** @var \App\Entity\Actor */
            $actor = $entityManager->getRepository(Actor::class)->findOneBy(['name' => $actorName]);
            if ($actor === null) {
                /** @var \App\Entity\Actor */
                $actor = new Actor();
                $actor->setName($actorName);
                $entityManager->persist($actor);
            }
            $series->addActor($actor);
        }

        $series->setTitle($title);
        $series->setPlot($plot);
        if ($director != "N/A") {
            $series->setDirector($director);
        }
        $series->setAwards($awards);
        $series->setYearStart($yearStart);
        if ($yearEnd) {
            $series->setYearEnd($yearEnd);
        }
        $series->setImdb($imdbID);
        $series->setPoster($data);
        $series->setYoutubeTrailer($trailer);
        $externalRating = new ExternalRating();
        $externalRating->setValue($imdbRating . '/10');
        /** @var \App\Entity\ExternalRatingSource */
        $externalRatingSource = $entityManager->getRepository(ExternalRatingSource::class)->findOneBy(['name' => 'Internet Movie Database']);
        $externalRating->setSource($externalRatingSource);
        $externalRating->setSeries($series);
        $series->setExternalRating($externalRating);



        if ($request->request->get('episodesData')) {
            $episodesData = $request->request->get('episodesData');
            $episodesData = json_decode($episodesData, true);
            foreach ($episodesData as $seasonData) {
                if (array_key_exists('Season', $seasonData)) {
                    /** @var \App\Entity\Season */
                    $season = new Season();
                    $seasonnumber = intval($seasonData['Season']);
                    $season->setNumber($seasonnumber);
                    $season->setSeries($series);
                    # for each episode in seasonData
                    foreach ($seasonData['Episodes'] as $episodeData) {
                        /** @var \App\Entity\Episode */
                        $episode = new Episode();
                        $episode->setNumber($episodeData['Episode']);
                        $episode->setTitle($episodeData['Title']);
                        $episode->setDate(new \DateTime($episodeData['Released']));
                        $imdbRating = floatval($episodeData['imdbRating']);
                        $episode->setImdbRating($imdbRating);
                        $episode->setImdb($episodeData['imdbID']);
                        $episode->setSeason($season);
                        $season->addEpisode($episode);
                        $entityManager->persist($episode);
                    }

                    $entityManager->persist($season);
                }
            }
        }




        $entityManager->persist($series);
        $entityManager->persist($externalRating);
        $entityManager->persist($actor);
        $entityManager->flush();
        $entityManager->clear();


        return $this->redirectToRoute('app_series_show', ['id' => $series->getId()]);
    }

    #[Route('/new', name: 'app_series_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var App\Entity\User */
        $userActual = $this->getUser();

        if (!$userActual || !$userActual->isAdmin()) {
            return $this->redirectToRoute('app_series_index', [], Response::HTTP_SEE_OTHER);
        }

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


        if ($val = $request->query->get('valueChoice')) {
            $ratings = $em->getRepository(Rating::class)->findBy(['series' => $series, 'value' => $val]);
        } else {
            $ratings = $series->getRatings();
        }

        // Vérifie si l'utilisateur courant a noté cette série
        if ($rating) {
            $userHasRated = true;
        } else {
            $userHasRated = false;
        }

        //Pagination des commentaires/critique des utilisateurs
        $appointmentsRatings = $paginator->paginate(
            $ratings,
            $request->query->getInt('page', 1),
            10,
        );

        // Récupère tous les épisodes de chaque saison
        foreach ($seasons as $season) {
            $episodesBySeason[$season->getNumber()] = $this->episodeRepository->findBySeason($season->getId());
        }

        $criticByValue = $this->compteNombreAvis($series, $em);


        // Retourne les détails de la série, les épisodes par saison, si l'utilisateur a noté la série, et les statistiques de notes
        return $this->render('series/show.html.twig', [
            'series' => $series,
            'episodesBySeason' => $episodesBySeason,
            'userHasRated' => $userHasRated,
            'ratings' => $appointmentsRatings,
            'criticByValue' => $criticByValue,
        ]);
    }

    /**
     * Compte le nombre de notes qui a sur une séries à une valeur de note donnée
     */
    private function compteNombreAvis(Series $series, EntityManagerInterface $em)
    {
        $rating = $em->getRepository(Rating::class)->findBy(['series' => $series]);
        $result = array();
        for ($i = 0; $i <= 10; $i++) {
            $result[$i] = 0;
        }
        foreach ($rating as $r) {
            if ($r->isEstModere() && !$r->getUser()->isEstSuspendu()) {
                $noteValue = $r->getValue();
                if (is_int($noteValue)) {
                    $result[$noteValue]++;
                } else {
                    $result[$noteValue]++;
                }
            }
        }

        return $result;
    }


    #[Route('/poster/{id}', name: 'app_poster_show', methods: ['GET', 'POST'])]
    public function shows(Series $series): Response
    {
        return new Response(stream_get_contents($series->getPoster()), 200, array('Content-Type' => 'image/png'));
    }

    #[Route('/{id}/edit', name: 'app_series_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Series $series, EntityManagerInterface $entityManager): Response
    {
        /** @var App\Entity\User */
        $userActual = $this->getUser();

        if (!$userActual || !$userActual->isAdmin()) {
            return $this->redirectToRoute('app_series_index', [], Response::HTTP_SEE_OTHER);
        }

        $form = $this->createForm(SeriesType::class, $series);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_series_show', ['id' => $series->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('series/edit.html.twig', [
            'series' => $series,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_series_delete', methods: ['POST'])]
    public function delete(Request $request, Series $series, EntityManagerInterface $entityManager): Response
    {
        /** @var App\Entity\User */
        $userActual = $this->getUser();

        if (!$userActual || !$userActual->isAdmin()) {
            return $this->redirectToRoute('app_series_index', [], Response::HTTP_SEE_OTHER);
        }

        if ($this->isCsrfTokenValid('delete' . $series->getId(), $request->request->get('_token'))) {
            $entityManager->remove($series);
            if ($series->getExternalRating() != null) {
                $this->deleteAllExternalRatingForDeleteSerie($series, $entityManager);
            }

            if (!empty($series->getRatings())) {
                $this->deleteAllRatingForDeleteSerie($series, $entityManager);
            }

            if (!empty($series->getSeasons())) {
                $this->deleteAllSeasonsForDeleteSerie($series, $entityManager);
            }



            $entityManager->flush();
        }

        return $this->redirectToRoute('app_series_index', [], Response::HTTP_SEE_OTHER);
    }

    private function deleteAllExternalRatingForDeleteSerie(Series $serie, EntityManagerInterface $entityManager)
    {
        $entityManager->remove($serie->getExternalRating());
    }

    private function deleteAllRatingForDeleteSerie(Series $serie, EntityManagerInterface $entityManager)
    {
        foreach ($serie->getRatings() as $rating) {
            $entityManager->remove($rating);
        }
    }

    private function deleteAllSeasonsForDeleteSerie(Series $serie, EntityManagerInterface $entityManager)
    {
        foreach ($serie->getSeasons() as $season) {
            $entityManager->remove($season);
            if (!empty($season->getEpisodes())) {
                foreach ($season->getEpisodes() as $episode) {
                    $entityManager->remove($episode);
                }
            }
        }
    }



    #[Route('/view/{id1}/{id2}', name: 'app_episode_view', methods: ['GET', 'POST'])]
    public function view(EntityManagerInterface $entityManager, Request $request): Response
    {
        # Récupère l'utilisateur connecté courant
        /** @var \App\Entity\User */
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login', [], Response::HTTP_SEE_OTHER);
        }

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

        if (!$user) {
            return $this->redirectToRoute('app_login', [], Response::HTTP_SEE_OTHER);
        }

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

        if (!$user) {
            return $this->redirectToRoute('app_login', [], Response::HTTP_SEE_OTHER);
        }

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

        if (!$user) {
            return $this->redirectToRoute('app_login', [], Response::HTTP_SEE_OTHER);
        }

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

        if (!$user) {
            return $this->redirectToRoute('app_login', [], Response::HTTP_SEE_OTHER);
        }

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

        if (!$user) {
            return $this->redirectToRoute('app_login', [], Response::HTTP_SEE_OTHER);
        }

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
