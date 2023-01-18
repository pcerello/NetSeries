<?php

namespace App\Controller;

use App\Entity\Episode;
use App\Entity\Season;
use App\Form\EpisodeType;
use App\Repository\EpisodeRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Entity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/episode')]
class EpisodeController extends AbstractController
{
    #[Route('/{idSeason}', name: 'app_episode_index', methods: ['GET'])]
    public function index(int $idSeason, EpisodeRepository $episodeRepository, EntityManagerInterface $entityManager): Response
    {
        
        //On récupère la saison associé à l'id mis dans l'URL
        $season = $entityManager->getRepository(Season::class)->findOneBy(['id' => $idSeason]);

        //On récupère tout les épisode associé à la saison concerné
        $episode = $season->getEpisodes();

        return $this->render('episode/index.html.twig', [
            'episodes' => $episode,
            'season' => $season,
        ]);
    }

    #[Route('/new/{idSeason}', name: 'app_episode_new', methods: ['GET', 'POST'])]
    public function new(int $idSeason, Request $request, EpisodeRepository $episodeRepository, EntityManagerInterface $entityManager): Response
    {
        //On récupère la saison associé à l'id mis dans l'URL
        $season = $entityManager->getRepository(Season::class)->findOneBy(['id' => $idSeason]);

        //création d'un nouvel épisode
        $episode = new Episode();
        $form = $this->createForm(EpisodeType::class, $episode);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $episodeRepository->save($episode, true);
            $season->addEpisode($episode);
            $entityManager->flush();

            return $this->redirectToRoute('app_episode_index', ['idSeason' => $season->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('episode/new.html.twig', [
            'episode' => $episode,
            'form' => $form,
            'season' => $season,
        ]);
    }

    #[Route('/{id}', name: 'app_episode_show', methods: ['GET'])]
    public function show(Episode $episode): Response
    {
        return $this->render('episode/show.html.twig', [
            'episode' => $episode,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_episode_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Episode $episode, EpisodeRepository $episodeRepository): Response
    {
        //On récupère la saison associé à l'épisode concerné
        $season = $episode->getSeason();

        $form = $this->createForm(EpisodeType::class, $episode);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $episodeRepository->save($episode, true);

            return $this->redirectToRoute('app_episode_index', ['idSeason' => $season->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('episode/edit.html.twig', [
            'episode' => $episode,
            'form' => $form,
            'season' => $episode->getSeason(),
        ]);
    }

    #[Route('/{id}', name: 'app_episode_delete', methods: ['POST'])]
    public function delete(Request $request, Episode $episode, EpisodeRepository $episodeRepository): Response
    {
        //On récupère la saison associé à l'épisode concerné
        $season = $episode->getSeason();

        if ($this->isCsrfTokenValid('delete' . $episode->getId(), $request->request->get('_token'))) {
            $episodeRepository->remove($episode, true);
        }

        return $this->redirectToRoute('app_episode_index', ['idSeason' => $season->getId()], Response::HTTP_SEE_OTHER);
    }
}
