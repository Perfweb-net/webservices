<?php

namespace App\Controller;

use App\Entity\Quiz;
use App\Service\FirebaseAuthService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Classe QuizController
 * 
 * Contrôleur de gestion des quiz
 * 
 * @package App\Controller
 * @category Controller
 * 
 * @version 1.0.0
 * 
 * @author Pierre SAUGUES <pierre.saugues@ynov.com>
 * @author Valentin FORTIN <valentin.fortin@ynov.com>
 */
#[Route(path: '/api/quiz', name: 'quiz_')]
class QuizController extends AbstractController
{
    //#region Constructeur
    /**
     * Constructeur
     * 
     * Initialise les dépendances du contrôleur
     * 
     * @access public
     * @since 1.0.0
     * 
     * @param FirebaseAuthService $firebaseAuthService Service d'authentification Firebase  
     * @param EntityManagerInterface $entityManager Interface d'entité
     * @param NormalizerInterface $normalizer Interface de normalisation
     * @param ValidatorInterface $validator Interface de validation
     */
    public function __construct(
        private readonly FirebaseAuthService $firebaseAuthService,
        private readonly EntityManagerInterface $entityManager,
        private readonly NormalizerInterface $normalizer,
        private readonly ValidatorInterface $validator,
    ) {
    }
    //#endregion

    //#region Méthodes
    /**
     * Méthode createQuiz
     * 
     * Permet à l'utilisateur authentifié 
     * de créer un quiz
     * 
     * @access public
     * @since 1.0.0
     * 
     * @param Request $request Requête HTTP
     * 
     * @return JsonResponse Réponse HTTP
     */
    #[Route(name: 'create', methods: ['POST'])]
    public function createQuiz(Request $request): JsonResponse
    {
        $user = $this->firebaseAuthService->getUserFromToken(request: $request);

        $params = json_decode(
            json: $request->getContent(),
            associative: true
        );

        $title = $params['title'];
        if (!$title) {
            throw new BadRequestHttpException(message: 'Title is required');
        }

        $description = $params['description'];
        if ($description === null) {
            throw new BadRequestHttpException(message: 'Description is required');
        }

        $quiz = new Quiz();
        $quiz->setTitle(title: $title);
        $quiz->setDescription(description: $description);
        $quiz->setOwner(owner: $user);

        $errors = $this->validator->validate(value: $quiz);

        if ($errors->count() > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getPropertyPath() . ' : ' . $error->getMessage();
            }

            $this->json(
                data: ['errors' => $errorMessages],
                status: Response::HTTP_BAD_REQUEST
            );
        }

        $this->entityManager->persist(object: $quiz);
        $this->entityManager->flush();

        $quizUrl = $this->generateUrl(
            route: 'quiz_get_one',
            parameters: ['quiz' => $quiz->getId()],
            referenceType: UrlGeneratorInterface::ABSOLUTE_URL
        );

        return $this->json(
            data: null,
            headers: ['Location' => $quizUrl],
            status: Response::HTTP_CREATED,
        );
    }

    /**
     * Méthode getUserQuizzes
     * 
     * Permet à l'utilisateur authentifié 
     * de récupérer la liste de ses quiz
     * 
     * @access public
     * @since 1.0.0
     * 
     * @param Request $request Requête HTTP
     * 
     * @return JsonResponse Réponse HTTP
     */
    #[Route(name: 'me_get_all', methods: ['GET'])]
    public function getUserQuizzes(Request $request): JsonResponse
    {
        $user = $this->firebaseAuthService->getUserFromToken(request: $request);
        $quizzes = $user->getQuizzes();

        // Normalisation des quizzes
        $quizzesData = $this->normalizer->normalize(
            $quizzes,
            null,
            ['groups' => ['quiz:read']]
        );

        // Ajout des liens uniquement pour les quizzes valides
        foreach ($quizzes as $index => $quiz) {
            if ($quiz->isValid()) {
                $quizzesData[$index]['_links']['start'] = $this->generateUrl(
                    route: 'quiz_start',
                    parameters: ['quiz' => $quiz->getId()],
                    referenceType: UrlGeneratorInterface::ABSOLUTE_URL
                );
            }
        }

        $createUrl = $this->generateUrl(
            route: 'quiz_create',
            referenceType: UrlGeneratorInterface::ABSOLUTE_URL
        );

        return $this->json(
            data: [
                'data' => $quizzesData,
                '_links' => ['create' => $createUrl]
            ],
            status: Response::HTTP_OK
        );
    }

    /**
     * Méthode getQuiz
     * 
     * Permet à l'utilisateur authentifié 
     * de récupérer un quiz
     * 
     * @access public
     * @since 1.0.0
     * 
     * @param Request $request Requête HTTP
     * @param Quiz $quiz Quiz
     * 
     * @return JsonResponse Réponse HTTP
     */
    #[Route(path: '/{quiz}', name: 'get_one', methods: ['GET'])]
    public function getQuiz(
        Request $request,
        Quiz $quiz
    ): JsonResponse {
        $user = $this->firebaseAuthService->getUserFromToken(request: $request);

        if ($quiz->getOwner() !== $user) {
            throw new NotFoundHttpException(
                message: 'Quiz not found'
            );
        }

        $quiz = $this->normalizer->normalize(
            $quiz,
            null,
            ['groups' => ['quiz:read', 'answer:read']]
        );

        return $this->json(
            data: $quiz,
            status: Response::HTTP_OK
        );
    }

    /**
     * Méthode patchQuiz
     * 
     * Permet à l'utilisateur authentifié
     * de modifier un quiz
     * 
     * @access public
     * @since 1.0.0
     * 
     * @param Request $request Requête HTTP
     * @param Quiz $quiz Quiz
     * 
     * @return JsonResponse Réponse HTTP
     */
    #[Route(path: '/{quiz}', name: 'patch_one', methods: ['PATCH'])]
    public function patchQuiz(
        Request $request,
        Quiz $quiz
    ): JsonResponse {
        $data = json_decode(
            json: $request->getContent(),
            associative: true
        );

        if (!is_array(value: $data) || !isset($data[0]['op'], $data[0]['path'], $data[0]['value'])) {
            throw new BadRequestHttpException(
                message: 'Invalid request body'
            );
        }

        $user = $this->firebaseAuthService->getUserFromToken(request: $request);

        if ($quiz->getOwner() !== $user) {
            throw new NotFoundHttpException(
                message: 'Quiz not found'
            );
        }

        if ($data[0]['path'] === '/title' && $data[0]['op'] === 'replace') {
            $quiz->setTitle(title: $data[0]['value']);
            $this->entityManager->flush();

            return $this->json(
                data: null,
                status: Response::HTTP_NO_CONTENT
            );
        }

        return $this->json(
            data: ['message' => 'Invalid request, the operation is not supported'],
            status: Response::HTTP_BAD_REQUEST
        );
    }

    /**
     * Méthode startQuiz
     * 
     * Permet à l'utilisateur authentifié
     * de démarrer un quiz
     * 
     * @access public
     * @since 1.0.0
     * 
     * @param Request $request Requête HTTP
     * @param Quiz $quiz Quiz
     * 
     * @return JsonResponse Réponse HTTP
     */
    #[Route(path: '/{quiz}/start', name: 'start', methods: ['POST'])]
    public function startQuiz(
        Request $request,
        Quiz $quiz,
    ): JsonResponse {
        $executionId = substr(md5(uniqid()), 0, 6);

        return new JsonResponse(null, Response::HTTP_CREATED, [
            'Location' => "/execution/{$executionId}"
        ]);    }
}
