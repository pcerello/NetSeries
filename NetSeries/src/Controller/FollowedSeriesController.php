<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/serieSuivi')]
class FollowedSeriesController extends AbstractController
{
    #[Route('/', name: 'app_followed_series')]
    public function index(): Response
    {
        // if the user is not logged in, redirect to the login page
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

        // if the user is logged in and is admin accessed by the isAdmin() method of User.php, show the user list
        /** @var \App\Entity\User */
        $user = $this->getUser();

        

        return $this->render('followed_series/index.html.twig', [
            'controller_name' => 'FollowedSeriesController',
        ]);
    }
}
