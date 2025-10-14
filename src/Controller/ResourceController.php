<?php

namespace App\Controller;

use App\Entity\Resource;
use App\Repository\ResourceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route("/api/resources")]
class ResourceController extends AbstractController
{
    #[Route("", name: "resources_index", methods: ["GET"])]
    public function index(ResourceRepository $resourceRepository, Request $request): JsonResponse
    {
        $status = $request->query->get("status");
        
        if ($status) {
            $resources = $resourceRepository->findBy(["status" => $status]);
        } else {
            $resources = $resourceRepository->findAll();
        }

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
        ValidatorInterface $validator
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        $resource = new Resource();
        $resource->setName($data["name"] ?? "");
        $resource->setDescription($data["description"] ?? null);
        $resource->setStatus($data["status"] ?? "active");
        
        // ВРЕМЕННО: создаем первого пользователя или используем существующего
        $user = $entityManager->getRepository(\App\Entity\User::class)->findOneBy([]);
        if ($user) {
            $resource->setUser($user);
        }

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
        $entityManager->remove($resource);
        $entityManager->flush();

        return $this->json(["message" => "Resource deleted successfully"]);
    }
}
