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
    public function index(BookingRepository , Request ): JsonResponse
    {
        // Фильтрация по статусу
         = ->query->get("status");
         = ->getUser();
        
        if () {
             = ->findBy(["status" => , "user" => ]);
        } else {
             = ->findBy(["user" => ]);
        }

         = [];
        foreach ( as ) {
            [] = [
                "id" => ->getId(),
                "resource" => [
                    "id" => ->getResource()->getId(),
                    "name" => ->getResource()->getName()
                ],
                "startTime" => ->getStartTime()->format("Y-m-d H:i:s"),
                "endTime" => ->getEndTime()->format("Y-m-d H:i:s"),
                "status" => ->getStatus(),
                "createdAt" => ->getCreatedAt()->format("Y-m-d H:i:s"),
                "updatedAt" => ->getUpdatedAt()->format("Y-m-d H:i:s"),
            ];
        }

        return ->json();
    }

    #[Route("", name: "bookings_create", methods: ["POST"])]
    public function create(
        Request ,
        EntityManagerInterface ,
        ResourceRepository ,
        ValidatorInterface 
    ): JsonResponse {
         = json_decode(->getContent(), true);

        // Проверяем обязательные поля
        if (!isset(["resourceId"]) || !isset(["startTime"]) || !isset(["endTime"])) {
            return ->json(["error" => "Resource ID, start time and end time are required"], Response::HTTP_BAD_REQUEST);
        }

        // Находим ресурс
         = ->find(["resourceId"]);
        if (!) {
            return ->json(["error" => "Resource not found"], Response::HTTP_NOT_FOUND);
        }

        // Проверяем доступность времени (нет пересечений бронирований)
         = new \DateTime(["startTime"]);
         = new \DateTime(["endTime"]);

         = ->getRepository(Booking::class)
            ->findOverlappingBooking(, , );
        
        if () {
            return ->json(["error" => "Time slot already booked"], Response::HTTP_CONFLICT);
        }

        // Создаем бронирование
         = new Booking();
        ->setResource();
        ->setUser(->getUser());
        ->setStartTime();
        ->setEndTime();
        ->setStatus(["status"] ?? "confirmed");

         = ->validate();
        if (count() > 0) {
             = [];
            foreach ( as ) {
                [] = ->getMessage();
            }

            return ->json(["errors" => ], Response::HTTP_BAD_REQUEST);
        }

        ->persist();
        ->flush();

        return ->json([
            "message" => "Booking created successfully",
            "bookingId" => ->getId()
        ], Response::HTTP_CREATED);
    }

    #[Route("/{id}", name: "bookings_update", methods: ["PUT"])]
    public function update(
        Request ,
        Booking ,
        EntityManagerInterface ,
        ValidatorInterface 
    ): JsonResponse {
        // Проверяем что бронирование принадлежит текущему пользователю
        if (->getUser()->getId() !== ->getUser()->getId()) {
            return ->json(["error" => "Access denied"], Response::HTTP_FORBIDDEN);
        }

         = json_decode(->getContent(), true);

        if (isset(["startTime"])) {
            ->setStartTime(new \DateTime(["startTime"]));
        }
        if (isset(["endTime"])) {
            ->setEndTime(new \DateTime(["endTime"]));
        }
        if (isset(["status"])) {
            ->setStatus(["status"]);
        }

        // Проверяем пересечения при изменении времени
        if (isset(["startTime"]) || isset(["endTime"])) {
             = ->getRepository(Booking::class)
                ->findOverlappingBooking(->getResource(), ->getStartTime(), ->getEndTime(), ->getId());
            
            if () {
                return ->json(["error" => "Time slot already booked"], Response::HTTP_CONFLICT);
            }
        }

         = ->validate();
        if (count() > 0) {
             = [];
            foreach ( as ) {
                [] = ->getMessage();
            }

            return ->json(["errors" => ], Response::HTTP_BAD_REQUEST);
        }

        ->flush();

        return ->json([
            "message" => "Booking updated successfully",
            "bookingId" => ->getId()
        ]);
    }

    #[Route("/{id}", name: "bookings_delete", methods: ["DELETE"])]
    public function delete(Booking , EntityManagerInterface ): JsonResponse
    {
        // Проверяем что бронирование принадлежит текущему пользователю
        if (->getUser()->getId() !== ->getUser()->getId()) {
            return ->json(["error" => "Access denied"], Response::HTTP_FORBIDDEN);
        }

        ->remove();
        ->flush();

        return ->json(["message" => "Booking deleted successfully"]);
    }
}
