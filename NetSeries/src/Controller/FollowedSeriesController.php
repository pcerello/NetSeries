<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Series;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Knp\Component\Pager\PaginatorInterface;

#[Route('/followedSeries')]
class FollowedSeriesController extends AbstractController
{
    #[Route('/', name: 'app_followed_series')]
    public function index(Request $request, EntityManagerInterface $entityManager, PaginatorInterface $paginator): Response
    {
        // if the user is not logged in, redirect to the login page
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

        // if the user is logged in and is admin accessed by the isAdmin() method of User.php, show the user list
        /** @var \App\Entity\User */
        $user = $this->getUser();

        $seriesFollowed = $user->getSeries();

        $seriesFollowedPaginated = $paginator->paginate(
            $seriesFollowed,
            $request->query->getInt('page', 1), /*page number*/
            7 /*limit per page*/
        );

        return $this->render('followed_series/index.html.twig', [
            'series' => $seriesFollowedPaginated,
        ]);
    }
}
