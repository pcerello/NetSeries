<?php

namespace App\Controller;

use App\Entity\ExternalRating;
use App\Entity\Series;
use App\Form\ExternalRatingType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/external/rating')]
class ExternalRatingController extends AbstractController
{
    #[Route('/{idSeries}', name: 'app_external_rating_index', methods: ['GET'])]
    public function index(int $idSeries, EntityManagerInterface $entityManager): Response
    {
        $serie = $entityManager->getRepository(Series::class)->findOneBy(['id' => $idSeries]);

        $externalRatings = $serie->getExternalRating();

        return $this->render('external_rating/index.html.twig', [
            'external_ratings' => $externalRatings,
            'serie' => $serie,
        ]);
    }

    #[Route('/new', name: 'app_external_rating_new', methods: ['GET', 'POST'])]
    public function new(int $idSeries, Request $request, EntityManagerInterface $entityManager): Response
    {
        $serie = $entityManager->getRepository(Series::class)->findOneBy(['id' => $idSeries]);

        $externalRating = new ExternalRating();
        $form = $this->createForm(ExternalRatingType::class, $externalRating);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($externalRating);
            $entityManager->flush();

            return $this->redirectToRoute('app_external_rating_index', ['idSeries' => $serie->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('external_rating/new.html.twig', [
            'external_rating' => $externalRating,
            'form' => $form,
            'serie' => $serie,
        ]);
    }

    #[Route('/{id}', name: 'app_external_rating_show', methods: ['GET'])]
    public function show(ExternalRating $externalRating): Response
    {
        return $this->render('external_rating/show.html.twig', [
            'external_rating' => $externalRating,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_external_rating_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ExternalRating $externalRating, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ExternalRatingType::class, $externalRating);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_external_rating_index', ['idSeries' => $externalRating->getSeries()->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('external_rating/edit.html.twig', [
            'external_rating' => $externalRating,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_external_rating_delete', methods: ['POST'])]
    public function delete(Request $request, ExternalRating $externalRating, EntityManagerInterface $entityManager): Response
    {
        $serie = $externalRating->getSeries();

        if ($this->isCsrfTokenValid('delete'.$externalRating->getId(), $request->request->get('_token'))) {
            $entityManager->remove($externalRating);
            $entityManager->flush();
        }
 
        return $this->redirectToRoute('app_external_rating_index', ['idSeries' => $serie->getId()], Response::HTTP_SEE_OTHER);
    }
}
