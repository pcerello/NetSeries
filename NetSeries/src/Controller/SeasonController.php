<?php

namespace App\Controller;

use App\Entity\Season;
use App\Entity\Series;
use App\Form\SeasonType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/season')]
class SeasonController extends AbstractController
{
    #[Route('/{idSeries}', name: 'app_season_index', methods: ['GET'])]
    public function index(int $idSeries, EntityManagerInterface $entityManager): Response
    {
        $serie = $entityManager->getRepository(Series::class)->findOneBy(['id' => $idSeries]);

        $seasons = $serie->getSeasons();

        return $this->render('season/index.html.twig', [
            'seasons' => $seasons,
            'serie' => $serie
        ]);
    }

    #[Route('/new/{idSeries}', name: 'app_season_new', methods: ['GET', 'POST'])]
    public function new(int $idSeries, Request $request, EntityManagerInterface $entityManager): Response
    {
        $serie = $entityManager->getRepository(Series::class)->findOneBy(['id' => $idSeries]);

        $season = new Season();
        $form = $this->createForm(SeasonType::class, $season);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($season);
            $serie->addSeason($season);
            $entityManager->flush();

            return $this->redirectToRoute('app_season_index', ['idSeries' => $serie->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('season/new.html.twig', [
            'season' => $season,
            'form' => $form,
            'serie' => $serie,
        ]);
    }

    #[Route('/{id}', name: 'app_season_show', methods: ['GET'])]
    public function show(Season $season): Response
    {
        return $this->render('season/show.html.twig', [
            'season' => $season,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_season_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Season $season, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(SeasonType::class, $season);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_season_index', ['idSeries' => $season->getSeries()->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('season/edit.html.twig', [
            'season' => $season,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_season_delete', methods: ['POST'])]
    public function delete(Request $request, Season $season, EntityManagerInterface $entityManager): Response
    {
        $serie = $season->getSeries();

        if ($this->isCsrfTokenValid('delete'.$season->getId(), $request->request->get('_token'))) {
            $entityManager->remove($season);
            
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_season_index', ['idSeries' => $serie->getId()], Response::HTTP_SEE_OTHER);
    }
}
