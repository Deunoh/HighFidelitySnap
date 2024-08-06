<?php

namespace App\Controller\Api;

use App\Entity\Image;
use App\Repository\ImageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api', name: 'app_api_image_', format: 'json')]
class HomeController extends AbstractController
{
    #[Route('/home', name: 'app_api_home')]
    public function index(): Response
    {
        return $this->render('home/index.html.twig', [
            'controller_name' => 'HomeController',
        ]);
    }
    #[Route('/image/{id<\d+>}', name:'read', methods: ['GET'])]
    public function read(int $id, ImageRepository $repository): JsonResponse
    {
        // Récupérer l'image par son identifiant
        $image = $repository->find($id);

        // Vérifier si l'image existe
        if (!$image) {
            // Retourner une réponse JSON avec une erreur 404 si l'image n'est pas trouvée
            return new JsonResponse(['error' => 'Image not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        // Retourner une réponse JSON avec les détails de l'image
        return new JsonResponse([
            'id' => $image->getId(),
            'link' => $image->getLink(),
            'device' => $image->getDevice(),
            'createdAt' => $image->getCreatedAt()->format('Y-m-d H:i:s'),
        ]);
    }

    #[Route('/image', name: 'add', methods: ['POST'])]
    public function add(EntityManagerInterface $em, Request $request, SerializerInterface $serializer, ValidatorInterface $validator): JsonResponse
    {
        $data = $request->request->all();
        dump($data);
        $image = $serializer->deserialize(json_encode($data), Image::class, 'json');

        $imageFile = $request->files->get('imageFile');
        if ($imageFile instanceof UploadedFile && $imageFile->isValid()) {
            $image->setImageFile($imageFile);
        } else {
            if (is_null($imageFile)) {
                return new JsonResponse(['error' => 'No image file provided'], JsonResponse::HTTP_BAD_REQUEST);
            } else {
                return new JsonResponse(['error' => 'Invalid image file'], JsonResponse::HTTP_BAD_REQUEST);
            }
        }

        // Validation de l'entité
        $errors = $validator->validate($image);
        if (count($errors) > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }

        // Enregistrer l'image dans la base de données
        $em->persist($image);
        $em->flush();

        // Définir l'URL de l'image après avoir persisté l'entité
        $imageUrl = $request->getSchemeAndHttpHost() . '/images/upload/' . $image->getImageFile()->getClientOriginalName();
        $image->setLink($imageUrl);

        // Mise à jour de l'entité avec le lien de l'image
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
