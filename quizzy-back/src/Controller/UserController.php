<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class UserController extends AbstractController
{
    private function getKeyId(string $token): string
    {
        // Décoder l'entête du JWT (sans vérifier la signature)
        $segments = explode('.', $token);
        if (count($segments) !== 3) {
            throw new \InvalidArgumentException('Invalid token');
        }

        // Décoder la partie de l'entête (Base64Url)
        $header = json_decode(base64_decode(strtr($segments[0], '-_', '+/')), true);

        if (!isset($header['kid'])) {
            throw new \InvalidArgumentException('Key ID (kid) not found in the token header');
        }

        return $header['kid'];
    }

    #[Route('/api/users', methods: ['POST'])]
    public function registerUser(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $userName = $data['username'] ?? null;

        if (!$userName) {
            return new JsonResponse(['error' => 'Username is required'], 400);
        }

        $authHeader = $request->headers->get('Authorization');

        // Vérifier la validité de l'en-tête Authorization
        if (!$authHeader || !preg_match('/^Bearer\s(\S+)$/', $authHeader, $matches)) {
            return new JsonResponse(['error' => 'No valid token found'], 401);
        }

        $token = $matches[1];

        try {
            // Récupérer les clés publiques de Firebase
            $client = new Client();

            try {
                $response = $client->get('https://www.googleapis.com/robot/v1/metadata/x509/securetoken@system.gserviceaccount.com');
                $keys = json_decode($response->getBody()->getContents(), true);
            } catch (RequestException $e) {
                // Gérer les erreurs de requête HTTP
                return new JsonResponse(['error' => 'Unable to retrieve public keys from Firebase', 'message' => $e->getMessage()], 500);
            }

            // Vérifier si la réponse contient des certificats
            if (empty($keys)) {
                return new JsonResponse(['error' => 'No keys found in Firebase response'], 500);
            }

            // Chercher la clé publique correspondant au kid dans l'entête du JWT
            $key = null;

            // Récupérer le Key ID du token
            $kid = $this->getKeyId($token);
            if (isset($keys[$kid])) {
                $key = $keys[$kid];
            }

            // Si la clé publique n'est pas trouvée
            if (!$key) {
                return new JsonResponse(['error' => 'Public key not found'], 401);
            }

            // Extraire la clé publique de Firebase à partir du certificat
            $publicKey = $key;

            // Décoder le token avec la clé publique
            $decodedToken = JWT::decode($token, new Key($publicKey, 'RS256'));

            // Extraire l'ID utilisateur
            $userId = $decodedToken->sub;

            //save user in database
            $user = new User();
            $user->setUuid($userId);
            $user->setUsername($userName);
            $entityManager->persist($user);
            $entityManager->flush();

            return new JsonResponse(['message' => 'User registered successfully'], 201);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Token decoding failed', 'message' => $e->getMessage()], 401);
        }
    }
}
