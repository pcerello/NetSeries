<?php

namespace App\Controller;

use App\Entity\Series;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DefaultController extends AbstractController
{
    public function __construct(private ManagerRegistry $doctrine) 
    {
        
    }

    #[Route('/', name: 'app_home')]
    public function index(ManagerRegistry $doctrine): Response
    {

        // Select 4 random series out of all series (optimized)
        $randomSeries = $this->doctrine->getRepository(Series::class)->findBy([], [], 4);

        return $this->render('home/index.html.twig', [
            'random_series' => $randomSeries,
        ]);
    }
}
