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
        //On récupère la série associé à l'id mis dans l'URL
        $serie = $entityManager->getRepository(Series::class)->findOneBy(['id' => $idSeries]);

        //On récupère les note IMBM associé à la série concerné
        $externalRatings = $serie->getExternalRating();

        return $this->render('external_rating/index.html.twig', [
            'externalRatings' => $externalRatings,
            'serie' => $serie,
        ]);
    }

    #[Route('/new/{idSeries}', name: 'app_external_rating_new', methods: ['GET', 'POST'])]
    public function new(int $idSeries, Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var App\Entity\User */
        $user = $this->getUser();

        //Si pas de compte connecté alors on lui dit de ce connecté
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        //Si l'utilisateur n'est pas un admin alors il peut pas venir sur la page des notes
        if (!$user->isAdmin()) {
            return $this->redirectToRoute('app_home');
        }

        //On récupère la série associé à l'id mis dans l'URL
        $serie = $entityManager->getRepository(Series::class)->findOneBy(['id' => $idSeries]);

        //Création d'une nouvelle note externe IMBM
        $externalRating = new ExternalRating();

        $form = $this->createForm(ExternalRatingType::class, $externalRating);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($externalRating);
            $externalRating->setSeries($serie);
            $serie->setExternalRating($externalRating);
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

    #[Route('/{id}/{idSerie}/edit', name: 'app_external_rating_edit', methods: ['GET', 'POST'])]
    public function edit(int $idSerie, Request $request, ExternalRating $externalRating, EntityManagerInterface $entityManager): Response
    {
        /** @var App\Entity\User */
        $user = $this->getUser();

        //Si pas de compte connecté alors on lui dit de ce connecté
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        //Si l'utilisateur n'est pas un admin alors il peut pas venir sur la page des notes
        if (!$user->isAdmin()) {
            return $this->redirectToRoute('app_home');
        }

        //On récupère la série associé à l'id mis dans l'URL
        $serie = $entityManager->getRepository(Series::class)->findOneBy(['id' => $idSerie]);

        $form = $this->createForm(ExternalRatingType::class, $externalRating);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_external_rating_index', ['idSeries' => $externalRating->getSeries()->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('external_rating/edit.html.twig', [
            'external_rating' => $externalRating,
            'form' => $form,
            'serie' => $serie,
        ]);
    }

    #[Route('/{id}', name: 'app_external_rating_delete', methods: ['POST'])]
    public function delete(Request $request, ExternalRating $externalRating, EntityManagerInterface $entityManager): Response
    {
        /** @var App\Entity\User */
        $user = $this->getUser();

        //Si pas de compte connecté alors on lui dit de ce connecté
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        //Si l'utilisateur n'est pas un admin alors il peut pas venir sur la page des notes
        if (!$user->isAdmin()) {
            return $this->redirectToRoute('app_home');
        }

        //On récupère la série associé à l'external rating (note externe IMBM)
        $serie = $externalRating->getSeries();

        if ($this->isCsrfTokenValid('delete' . $externalRating->getId(), $request->request->get('_token'))) {
            $entityManager->remove($externalRating);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_external_rating_index', ['idSeries' => $serie->getId()], Response::HTTP_SEE_OTHER);
    }
}
