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
    ) {
    }
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
        $user = $repository->find(id: $userId);

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
}
