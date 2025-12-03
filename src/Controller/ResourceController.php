<?php

namespace App\Controller;

use App\Entity\Resource;
use App\Entity\User;
use App\Repository\ResourceRepository;
use App\Message\ResourceCreatedMessage;

use Doctrine\ORM\EntityManagerInterface;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Messenger\MessageBusInterface;


#[Route("/api/resources")]
class ResourceController extends AbstractController
{
    #[Route("", name: "resources_index", methods: ["GET"])]
    public function index(ResourceRepository $resourceRepository, Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->json(["error" => "User not authenticated"], Response::HTTP_UNAUTHORIZED);
        }
        
        $status = $request->query->get("status");
        $name = $request->query->get("name"); // Новый параметр фильтра по названию
        
        // Используем Query Builder для сложных запросов
        $qb = $resourceRepository->createQueryBuilder('r')
            ->where('r.user = :user')
            ->setParameter('user', $user);
        
        if ($status) {
            $qb->andWhere('r.status = :status')
               ->setParameter('status', $status);
        }
        
        if ($name) {
            $qb->andWhere('r.name LIKE :name')
               ->setParameter('name', '%' . $name . '%');
        }
        
        $qb->orderBy('r.createdAt', 'DESC');
        
        $resources = $qb->getQuery()->getResult();

        $data = [];
        foreach ($resources as $resource) {
            $data[] = [
                "id" => $resource->getId(),
                "name" => $resource->getName(),
                "description" => $resource->getDescription(),
                "status" => $resource->getStatus(),
                "userId" => $resource->getUser()?->getId(),
                "createdAt" => $resource->getCreatedAt()->format("Y-m-d H:i:s"),
                "updatedAt" => $resource->getUpdatedAt()->format("Y-m-d H:i:s"),
            ];
        }

        return $this->json($data);
    }

    #[Route("", name: "resources_create", methods: ["POST"])]
    public function create(
        Request $request,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
        MessageBusInterface $bus
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();
        
        if (!$user) {
            return $this->json(["error" => "User not authenticated"], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);

        $resource = new Resource();
        $resource->setName($data["name"] ?? "");
        $resource->setDescription($data["description"] ?? null);
        $resource->setStatus($data["status"] ?? "active");
        $resource->setUser($user); // Привязываем к текущему пользователю

        $errors = $validator->validate($resource);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }

            return $this->json(["errors" => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        $entityManager->persist($resource);
        $entityManager->flush();

        $bus->dispatch(new ResourceCreatedMessage(
            $resource->getId(),
            $resource->getName()
        ));

        return $this->json([
            "message" => "Resource created successfully",
            "resourceId" => $resource->getId()
        ], Response::HTTP_CREATED);
    }

    #[Route("/{id}", name: "resources_update", methods: ["PUT"])]
    public function update(
        Request $request,
        Resource $resource,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator
    ): JsonResponse {

        $this->checkResourceOwnership($resource);

        $data = json_decode($request->getContent(), true);

        if (isset($data["name"])) {
            $resource->setName($data["name"]);
        }
        if (isset($data["description"])) {
            $resource->setDescription($data["description"]);
        }
        if (isset($data["status"])) {
            $resource->setStatus($data["status"]);
        }

        $errors = $validator->validate($resource);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }

            return $this->json(["errors" => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        $entityManager->flush();

        return $this->json([
            "message" => "Resource updated successfully",
            "resourceId" => $resource->getId()
        ]);
    }

    #[Route("/{id}", name: "resources_delete", methods: ["DELETE"])]
    public function delete(Resource $resource, EntityManagerInterface $entityManager): JsonResponse
    {
        $this->checkResourceOwnership($resource);

        $entityManager->remove($resource);
        $entityManager->flush();

        return $this->json(["message" => "Resource deleted successfully"]);
    }

    #[Route("/{id}", name: "resources_show", methods: ["GET"])]
    public function show(Resource $resource): JsonResponse
    {
        $this->checkResourceOwnership($resource);

        $data = [
            "id" => $resource->getId(),
            "name" => $resource->getName(),
            "description" => $resource->getDescription(),
            "status" => $resource->getStatus(),
            "userId" => $resource->getUser()?->getId(),
            "createdAt" => $resource->getCreatedAt()->format("Y-m-d H:i:s"),
            "updatedAt" => $resource->getUpdatedAt()->format("Y-m-d H:i:s"),
        ];

        return $this->json($data);
    }

    //Проверяет, принадлежит ли ресурс текущему пользователю
    private function checkResourceOwnership(Resource $resource): void
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();
        
        if (!$currentUser) {
            throw new AccessDeniedException('User not authenticated');
        }

        $resourceUser = $resource->getUser();
        
        if (!$resourceUser || $resourceUser->getId() !== $currentUser->getId()) {
            throw new AccessDeniedException('You can only access your own resources');
        }
    }
}