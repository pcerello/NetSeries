<?php

namespace App\Controller;

use App\Entity\Genre;
use App\Entity\Series;
use App\Form\GenreType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/genre')]
class GenreController extends AbstractController
{
    #[Route('/{idSeries}', name: 'app_genre_index', methods: ['GET'])]
    public function index(int $idSeries, EntityManagerInterface $entityManager): Response
    {
        //On récupère la série associé à l'id mis dans l'URL
        $serie = $entityManager->getRepository(Series::class)->findOneBy(['id' => $idSeries]);

        //On récupère tout les genres de la série concerné
        $genres = $serie->getGenre();

        return $this->render('genre/index.html.twig', [
            'genres' => $genres,
            'serie' => $serie,
        ]);
    }

    #[Route('/new/{idSerie}', name: 'app_genre_new', methods: ['GET', 'POST'])]
    public function new(int $idSerie, Request $request, EntityManagerInterface $entityManager): Response
    {
        //On récupère la série associé à l'id mis dans l'URL
        $serie = $entityManager->getRepository(Series::class)->findOneBy(['id' => $idSerie]);

        //Création d'un nouveau genre
        $genre = new Genre();

        $form = $this->createForm(GenreType::class, $genre);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            //On l'ajoute à la base de données
            $entityManager->persist($genre);
            $serie->addGenre($genre);
            $entityManager->flush();

            return $this->redirectToRoute('app_genre_index', ['idSeries' => $serie->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('genre/new.html.twig', [
            'genre' => $genre,
            'form' => $form,
            'serie' => $serie,
        ]);
    }

    #[Route('/{id}', name: 'app_genre_show', methods: ['GET'])]
    public function show(Genre $genre): Response
    {
        return $this->render('genre/show.html.twig', [
            'genre' => $genre,
        ]);
    }

    #[Route('/{id}/{idSerie}/edit/', name: 'app_genre_edit', methods: ['GET', 'POST'])]
    public function edit(int $idSerie, Request $request, Genre $genre, EntityManagerInterface $entityManager): Response
    {
        //On récupère la série associé à l'id mis dans l'URL
        $serie = $entityManager->getRepository(Series::class)->findOneBy(['id' => $idSerie]);

        $form = $this->createForm(GenreType::class, $genre);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            //Met à jour la base de donnée
            $entityManager->flush();

            return $this->redirectToRoute('app_genre_index', ['idSeries' => $serie->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('genre/edit.html.twig', [
            'genre' => $genre,
            'form' => $form,
            'serie' => $serie,
        ]);
    }

    #[Route('/{id}/{idSerie}', name: 'app_genre_delete', methods: ['POST'])]
    public function delete(int $idSerie, Request $request, Genre $genre, EntityManagerInterface $entityManager): Response
    {
        //On récupère la série associé à l'id mis dans l'URL
        $serie = $entityManager->getRepository(Series::class)->findOneBy(['id' => $idSerie]);

        if ($this->isCsrfTokenValid('delete'.$genre->getId(), $request->request->get('_token'))) {
            $entityManager->remove($genre);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_genre_index', ['idSeries' => $serie->getId()], Response::HTTP_SEE_OTHER);
    }
}
