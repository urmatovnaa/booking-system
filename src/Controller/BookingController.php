<?php

namespace App\Controller;

use App\Entity\Booking;
use App\Entity\Resource;
use App\Repository\BookingRepository;
use App\Repository\ResourceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route("/api/bookings")]
class BookingController extends AbstractController
{
    #[Route("", name: "bookings_index", methods: ["GET"])]
    public function index(BookingRepository $bookingRepository, Request $request): JsonResponse
    {
        $status = $request->query->get("status");
        
        if ($status) {
            $bookings = $bookingRepository->findBy(["status" => $status]);
        } else {
            $bookings = $bookingRepository->findAll();
        }

        $data = [];
        foreach ($bookings as $booking) {
            $data[] = [
                "id" => $booking->getId(),
                "resource" => [
                    "id" => $booking->getResource()->getId(),
                    "name" => $booking->getResource()->getName()
                ],
                "startTime" => $booking->getStartTime()->format("Y-m-d H:i:s"),
                "endTime" => $booking->getEndTime()->format("Y-m-d H:i:s"),
                "status" => $booking->getStatus(),
                "userId" => $booking->getUser()?->getId(),
                "createdAt" => $booking->getCreatedAt()->format("Y-m-d H:i:s"),
                "updatedAt" => $booking->getUpdatedAt()->format("Y-m-d H:i:s"),
            ];
        }

        return $this->json($data);
    }

    #[Route("", name: "bookings_create", methods: ["POST"])]
    public function create(
        Request $request,
        EntityManagerInterface $entityManager,
        ResourceRepository $resourceRepository,
        ValidatorInterface $validator
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!isset($data["resourceId"]) || !isset($data["startTime"]) || !isset($data["endTime"])) {
            return $this->json(["error" => "Resource ID, start time and end time are required"], Response::HTTP_BAD_REQUEST);
        }

        $resource = $resourceRepository->find($data["resourceId"]);
        if (!$resource) {
            return $this->json(["error" => "Resource not found"], Response::HTTP_NOT_FOUND);
        }

        $startTime = new \DateTime($data["startTime"]);
        $endTime = new \DateTime($data["endTime"]);

        $existingBooking = $entityManager->getRepository(Booking::class)
            ->findOverlappingBooking($resource, $startTime, $endTime);
        
        if ($existingBooking) {
            return $this->json(["error" => "Time slot already booked"], Response::HTTP_CONFLICT);
        }

        // Получаем текущего аутентифицированного пользователя
        $user = $this->getUser();
        if (!$user) {
            return $this->json(["error" => "User not authenticated"], Response::HTTP_UNAUTHORIZED);
        }

        $booking = new Booking();
        $booking->setResource($resource);
        $booking->setUser($user); // Устанавливаем текущего пользователя
        $booking->setStartTime($startTime);
        $booking->setEndTime($endTime);
        $booking->setStatus($data["status"] ?? "confirmed");

        $errors = $validator->validate($booking);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }

            return $this->json(["errors" => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        $entityManager->persist($booking);
        $entityManager->flush();

        return $this->json([
            "message" => "Booking created successfully",
            "bookingId" => $booking->getId()
        ], Response::HTTP_CREATED);
    }

    #[Route("/{id}", name: "bookings_update", methods: ["PUT"])]
    public function update(
        Request $request,
        Booking $booking,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (isset($data["startTime"])) {
            $booking->setStartTime(new \DateTime($data["startTime"]));
        }
        if (isset($data["endTime"])) {
            $booking->setEndTime(new \DateTime($data["endTime"]));
        }
        if (isset($data["status"])) {
            $booking->setStatus($data["status"]);
        }

        if (isset($data["startTime"]) || isset($data["endTime"])) {
            $existingBooking = $entityManager->getRepository(Booking::class)
                ->findOverlappingBooking($booking->getResource(), $booking->getStartTime(), $booking->getEndTime(), $booking->getId());
            
            if ($existingBooking) {
                return $this->json(["error" => "Time slot already booked"], Response::HTTP_CONFLICT);
            }
        }

        $errors = $validator->validate($booking);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }

            return $this->json(["errors" => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        $entityManager->flush();

        return $this->json([
            "message" => "Booking updated successfully",
            "bookingId" => $booking->getId()
        ]);
    }

    #[Route("/{id}", name: "bookings_delete", methods: ["DELETE"])]
    public function delete(Booking $booking, EntityManagerInterface $entityManager): JsonResponse
    {
        $entityManager->remove($booking);
        $entityManager->flush();

        return $this->json(["message" => "Booking deleted successfully"]);
    }
}
