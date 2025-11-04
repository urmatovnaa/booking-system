<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class FrontendController extends AbstractController
{
    #[Route('/', name: 'app_frontend', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('app/index.html.twig', ['page' => '']);
    }

    #[Route('/login', name: 'app_login', methods: ['GET'])]
    public function login(): Response
    {
        return $this->render('app/index.html.twig', ['page' => 'login']);
    }

    #[Route('/resources', name: 'app_resources', methods: ['GET'])]
    public function resources(): Response
    {
        return $this->render('app/index.html.twig', ['page' => 'resources']);
    }

    #[Route('/bookings', name: 'app_bookings', methods: ['GET'])]
    public function bookings(): Response
    {
        return $this->render('app/index.html.twig', ['page' => 'bookings']);
    }

    #[Route('/resources/{id}', name: 'app_resource_show', requirements: ['id' => '\\d+'], methods: ['GET'])]
    public function resourceShow(int $id): Response
    {
        return $this->render('app/resource_show.html.twig', ['id' => $id]);
    }

    #[Route('/bookings/{id}', name: 'app_booking_show', requirements: ['id' => '\\d+'], methods: ['GET'])]
    public function bookingShow(int $id): Response
    {
        return $this->render('app/booking_show.html.twig', ['id' => $id]);
    }
}


