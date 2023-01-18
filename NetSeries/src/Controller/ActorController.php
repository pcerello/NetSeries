<?php

namespace App\Controller;

use App\Entity\Actor;
use App\Entity\Series;
use App\Form\ActorType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/actor')]
class ActorController extends AbstractController
{
    #[Route('/{idSeries}', name: 'app_actor_index', methods: ['GET'])]
    public function index(int $idSeries, EntityManagerInterface $entityManager): Response
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

        //On récupère les acteurs associé à la série
        $actors = $serie->getActor();

        return $this->render('actor/index.html.twig', [
            'actors' => $actors,
            'serie' => $serie,
        ]);
    }

    #[Route('/new/{idSerie}', name: 'app_actor_new', methods: ['GET', 'POST'])]
    public function new(int $idSerie, Request $request, EntityManagerInterface $entityManager): Response
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

        //On crée un nouvel acteur
        $actor = new Actor();

        $form = $this->createForm(ActorType::class, $actor);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($actor);
            $serie->addActor($actor);
            $entityManager->flush();

            return $this->redirectToRoute('app_actor_index', ['idSeries' => $serie->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('actor/new.html.twig', [
            'actor' => $actor,
            'form' => $form,
            'serie' => $serie,
        ]);
    }

    #[Route('/{id}', name: 'app_actor_show', methods: ['GET'])]
    public function show(Actor $actor): Response
    {
        return $this->render('actor/show.html.twig', [
            'actor' => $actor,
        ]);
    }

    #[Route('/{id}/{idSerie}/edit', name: 'app_actor_edit', methods: ['GET', 'POST'])]
    public function edit(int $idSerie, Request $request, Actor $actor, EntityManagerInterface $entityManager): Response
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

        $form = $this->createForm(ActorType::class, $actor);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_actor_index', ['idSeries' => $serie->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('actor/edit.html.twig', [
            'actor' => $actor,
            'form' => $form,
            'serie' => $serie,
        ]);
    }

    #[Route('/{id}/{idSerie}', name: 'app_actor_delete', methods: ['POST'])]
    public function delete(int $idSerie, Request $request, Actor $actor, EntityManagerInterface $entityManager): Response
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

        if ($this->isCsrfTokenValid('delete' . $actor->getId(), $request->request->get('_token'))) {
            $entityManager->remove($actor);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_actor_index', ['idSeries' => $serie->getId()], Response::HTTP_SEE_OTHER);
    }
}
