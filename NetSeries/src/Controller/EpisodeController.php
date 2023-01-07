<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\EpisodeRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Controller\Request;

class EpisodeController extends AbstractController
{
    private $episodeRepository;
    private $entityManager;

    public function __construct(EpisodeRepository $episodeRepository, EntityManagerInterface $entityManager)
    {
        $this->episodeRepository = $episodeRepository;
        $this->entityManager = $entityManager;
    }

    #[Route('/episode/{id}', name: 'app_episode_show')]
    public function show($season): Response
    {
        $episodes = $this->episodeRepository->findBySeason($season);

        return $this->render('episode/show.html.twig', [
            'episodes' => $episodes,
        ]);
    }

}
