<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\FirebaseAuthService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


/**
 * Classe UserController
 * 
 * Contrôleur de gestion des utilisateurs
 * 
 * @package App\Controller
 * @category Controller
 * 
 * @version 1.0.0
 * 
 * @author Pierre SAUGUES <pierre.saugues@ynov.com>
 * @author Valentin FORTIN <valentin.fortin@ynov.com>
 */
#[Route(path: '/api', name: 'user_')]
class UserController extends AbstractController
{
  //#region Constructeur
  /**
   * Constructeur
   * 
   * Initialise les dépandances du contrôleur
   * 
   * @access public
   * @since 1.0.0
   * 
   * @param FirebaseAuthService $firebaseAuthService Service d'authentification Firebase
   * @param EntityManagerInterface $entityManager Interface d'entité
   */
  public function __construct(
    private readonly FirebaseAuthService $firebaseAuthService,
    private readonly EntityManagerInterface $entityManager
  ) {}
  //#endregion

  //#region Méthodes
  /**
   * Méthode register
   * 
   * Router permettant l'incription 
   * d'un utilisateur.
   * 
   * @access public
   * @since 1.0.0
   * 
   * @param Request $request Requête
   * 
   * @return JsonResponse Réponse JSON
   */
  #[Route(path: '/users', methods: ['POST'], name: 'register')]
  public function register(Request $request): JsonResponse 
  {
    $data = json_decode(
      json: $request->getContent(), 
      associative: true
    );
    
    $userName = $data['username'] ?? null;

    if (!$userName) {
      throw new BadRequestHttpException(
        message: 'Username is required'
      );
    }

    try {
      $token = $this->firebaseAuthService->extractBearerToken(request: $request);
      if (!$token) {
        throw new UnauthorizedHttpException(
          challenge: 'Bearer',
          message: 'No valid token found'
        );
      }

      // Utiliser le service pour vérifier le token
      $decodedToken = $this->firebaseAuthService->verifyToken(token: $token);

      // Extraire l'ID utilisateur
      $userId = $decodedToken->sub;

      // Vérifier si l'utilisateur existe déjà
      $repository = $this->entityManager->getRepository(className: User::class);
      $existingUser = $repository->findOneBy(criteria: ['uid' => $userId]);

      if ($existingUser) {
        throw new ConflictHttpException(
          message: 'User already exists'
        );
      }

      // Enregistrer le nouvel utilisateur dans la base de données
      $user = new User();
      $user->setUid(uid: $userId);
      $user->setUsername(username: $userName);

      $this->entityManager->persist(object: $user);
      $this->entityManager->flush();

      return $this->json(
        data: ['message' => 'User registered successfully'],
        status: Response::HTTP_CREATED
      );
    } catch (Exception $exception) {
      throw new UnauthorizedHttpException(
        challenge: 'Bearer',
        message: 'Token decoding failed',
        previous: $exception
      );
    }
  }

  /**
   * Méthode me
   * 
   * Router permettant de récupérer les informations 
   * de l'utilisateur connecté.
   * 
   * @access public
   * @since 1.0.0
   * 
   * @param Request $request Requête
   * 
   * @return JsonResponse Réponse JSON
   */
  #[Route(path: '/users/me', methods: ['GET'], name: 'me')]
  public function me(Request $request): JsonResponse
  {
    $token = $this->firebaseAuthService->extractBearerToken(request: $request);
    if (!$token) {
      throw new UnauthorizedHttpException(
        challenge: 'Bearer',
        message: 'No valid token found'
      );
    }

    $decodedToken = $this->firebaseAuthService->verifyToken(token: $token);
    $userId = $decodedToken->sub;

    $repository = $this->entityManager->getRepository(className: User::class);
    $user = $repository->findOneBy(criteria: ['uid' => $userId]);

    if (!$user) {
      throw new UnauthorizedHttpException(
        challenge: 'Bearer',
        message: 'User not found'
      );
    }

    return $this->json(
      data: [
        'uid' => $user->getUid(),
        'username' => $user->getUsername(),
        'email' => $decodedToken->email
      ]
    );
  }
  //#endregion

  // #[Route('/api/users', methods: ['POST'])]
  // public function registerUser(Request $request, EntityManagerInterface $entityManager): JsonResponse
  // {
  //   $data = json_decode($request->getContent(), true);
  //   $userName = $data['username'] ?? null;

  //   if (!$userName) {
  //     return new JsonResponse(['error' => 'Username is required'], 400);
  //   }

  //   $authHeader = $request->headers->get('Authorization');

  //   // Vérifier la validité de l'en-tête Authorization
  //   if (!$authHeader || !preg_match('/^Bearer\s(\S+)$/', $authHeader, $matches)) {
  //     return new JsonResponse(['error' => 'No valid token found'], 401);
  //   }

  //   $token = $matches[1];

  //   try {
  //     // Récupérer les clés publiques de Firebase
  //     $client = new Client();

  //     try {
  //       $response = $client->get('https://www.googleapis.com/robot/v1/metadata/x509/securetoken@system.gserviceaccount.com');
  //       $keys = json_decode($response->getBody()->getContents(), true);
  //     } catch (RequestException $e) {
  //       // Gérer les erreurs de requête HTTP
  //       return new JsonResponse(['error' => 'Unable to retrieve public keys from Firebase', 'message' => $e->getMessage()], 500);
  //     }

  //     // Vérifier si la réponse contient des certificats
  //     if (empty($keys)) {
  //       return new JsonResponse(['error' => 'No keys found in Firebase response'], 500);
  //     }

  //     // Chercher la clé publique correspondant au kid dans l'entête du JWT
  //     $key = null;

  //     // Récupérer le Key ID du token
  //     $kid = $this->getKeyId($token);
  //     if (isset($keys[$kid])) {
  //       $key = $keys[$kid];
  //     }

  //     // Si la clé publique n'est pas trouvée
  //     if (!$key) {
  //       return new JsonResponse(['error' => 'Public key not found'], 401);
  //     }

  //     // Extraire la clé publique de Firebase à partir du certificat
  //     $publicKey = $key;

  //     // Décoder le token avec la clé publique
  //     $decodedToken = JWT::decode($token, new Key($publicKey, 'RS256'));

  //     // Extraire l'ID utilisateur
  //     $userId = $decodedToken->sub;

  //     //save user in database
  //     $user = new User();
  //     $user->setUid($userId);
  //     $user->setUsername($userName);
  //     $entityManager->persist($user);
  //     $entityManager->flush();

  //     return new JsonResponse(['message' => 'User registered successfully'], 201);
  //   } catch (\Exception $e) {
  //     return new JsonResponse(['error' => 'Token decoding failed', 'message' => $e->getMessage()], 401);
  //   }
  // }
}
