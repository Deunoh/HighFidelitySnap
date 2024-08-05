<?php

namespace App\Controller\Api;

use App\Entity\Image;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class HomeController extends AbstractController
{
    #[Route('/api/home', name: 'app_api_home')]
    public function index(): Response
    {
        return $this->render('home/index.html.twig', [
            'controller_name' => 'HomeController',
        ]);
    }
    
    #[Route('image', name:'add', methods: 'POST')]
    public function add(EntityManagerInterface $em, Request $request, SerializerInterface $serializer, ValidatorInterface $validator): JsonResponse
    {
        $imageFile = $request->files->get('image');
        $device = $request->request->get('device');
        if (!$imageFile) {
            return new JsonResponse(['error' => 'No image file provided'], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Créer une nouvelle instance de l'entité Image
        $image = new Image();
        $image->setDevice($device);
        $image->setCreatedAt(new \DateTimeImmutable());

        // Gérer le téléchargement de l'image
        try {
            $image->setImageFile($imageFile);
        } catch (FileException $e) {
            return new JsonResponse(['error' => $e->getMessage()], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }

        // Validation de l'entité
        $errors = $validator->validate($image);
        if (count($errors) > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }

        // Enregistrer l'image dans la base de données
        $em->persist($image);
        $em->flush();

        // Retourner une réponse JSON avec le lien de l'image
        return new JsonResponse([
            'id' => $image->getId(),
            'link' => $image->getLink(),
            'device' => $image->getDevice(),
            'createdAt' => $image->getCreatedAt()->format('Y-m-d H:i:s'),
        ], JsonResponse::HTTP_CREATED);
    }
}
