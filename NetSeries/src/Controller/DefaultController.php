<?php

namespace App\Controller;

use App\Entity\Series;
use Doctrine\ORM\EntityManagerInterface;
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
    public function index(EntityManagerInterface $em): Response
    {

        // Select 4 random series using native sql
        $sql = "SELECT * FROM series ORDER BY RAND() LIMIT 4";

        // Create the ResultSetMapping object
        $rsm = new \Doctrine\ORM\Query\ResultSetMapping();

        // Add entity results
        $rsm->addEntityResult(Series::class, 's');

        // Map data columns to entity properties
        $rsm->addFieldResult('s', 'id', 'id');
        $rsm->addFieldResult('s', 'title', 'title');
        $rsm->addFieldResult('s', 'description', 'description');
        $rsm->addFieldResult('s', 'image', 'image');

        // Create the query
        $query = $em->createNativeQuery($sql, $rsm);

        $search = $query->getResult();

        // Convert the result to an array
        $search = array_map(function ($series) {
            return [
                'id' => $series->getId(),
                'title' => $series->getTitle(),
                'description' => $series->getPlot(),
                'image' => $series->getPoster(),
                'externalRating' => $series->getExternalRating(),
            ];
        }, $search);

        return $this->render('home/index.html.twig', [
            'random_series' => $search,
        ]);
    }
}
