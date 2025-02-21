<?php

namespace App\Controller;

use App\Entity\Answer;
use App\Entity\Question;
use App\Entity\Quiz;
use App\Repository\AnswerRepository;
use App\Repository\QuestionRepository;
use App\Service\FirebaseAuthService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use OpenApi\Attributes as OA;

/**
 * Classe QuestionController
 * @final
 * 
 * Contrôleur de gestion des questions
 * 
 * @package App\Controller
 * @category Controller
 * 
 * @version 1.0.0
 * 
 * @author Pierre SAUGUES <pierre.saugues@ynov.com>
 * @author Valentin FORTIN <valentin.fortin@ynov.com>
 */
#[OA\Tag(name: 'Quiz Questions')]
#[Route(path: '/api/quiz/{quiz}', name: 'questions_')]
final class QuestionController extends AbstractController
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
     * @param NormalizerInterface $normalizer Interface de normalisation
     * @param ValidatorInterface $validator Interface de validation
     * @param QuestionRepository $questionRepository Dépôt de questions
     * @param AnswerRepository $answerRepository Dépôt de réponses
     */
    public function __construct(
        private readonly FirebaseAuthService $firebaseAuthService,
        private readonly EntityManagerInterface $entityManager,
        private readonly NormalizerInterface $normalizer,
        private readonly ValidatorInterface $validator,
        private readonly QuestionRepository $questionRepository,
        private readonly AnswerRepository $answerRepository,
    ) {
    }
    //#endregion

    //#region Propriétés
    /**
     * Méthode addQuestions
     * 
     * Router permettant l'ajout de questions
     * à un quiz.
     * 
     * @access public
     * @since 1.0.0
     * 
     * @param Request $request Requête
     * @param Quiz $quiz Quiz
     * 
     * @return JsonResponse Réponse HTTP
     */
    #[Route(path: '/questions', name: 'add', methods: ["POST"])]
    public function addQuestions(Request $request, Quiz $quiz): JsonResponse
    {
        $user = $this->firebaseAuthService->getUserFromToken(request: $request);

        if ($quiz->getOwner() !== $user) {
            throw new AccessDeniedHttpException(
                message: 'You are not the owner of this quiz.'
            );
        }

        $data = json_decode(
            json: $request->getContent(),
            associative: true
        );

        if (!is_array(value: $data) || !isset($data["title"])) {
            throw new BadRequestHttpException(
                message: 'Title is required.'
            );
        }

        $question = new Question();
        $question->setTitle(title: $data["title"]);
        $question->setQuiz(quiz: $quiz);

        if (!empty($data["answers"])) {
            foreach ($data["answers"] as $answer) {
                $answerNew = (new Answer())
                    ->setTitle(title: $answer["title"] ?? '')
                    ->setIsCorrect(isCorrect: $answer["isCorrect"] ?? false)
                    ->setQuestion(question: $question);

                $this->entityManager->persist(object: $answerNew);

                $question->addAnswer(answer: $answerNew);
            }
        }

        $this->entityManager->persist(object: $question);
        $this->entityManager->flush();

        $questionUrl = $this->generateUrl(
            route: 'questions_get_one',
            parameters: ['quiz' => $quiz->getId(), 'question' => $question->getId()],
            referenceType: UrlGeneratorInterface::ABSOLUTE_URL
        );

        return $this->json(
            data: null,
            headers: ['Location' => $questionUrl],
            status: Response::HTTP_CREATED,
        );
    }

    /**
     * Méthode putQuestion
     * 
     * Router permettant la modification d'une question
     * 
     * @access public
     * @since 1.0.0
     * 
     * @param Request $request Requête
     * @param Quiz $quiz Quiz
     * @param Question $question Question
     * 
     * @return JsonResponse Réponse HTTP
     */
    #[Route(path: '/questions/{question}', name: 'put_one', methods: ["PUT"])]
    public function putQuestion(
        Request $request,
        Quiz $quiz,
        Question $question
    ): JsonResponse {
        $user = $this->firebaseAuthService->getUserFromToken(request: $request);

        // Vérifier si l'utilisateur est le propriétaire du quiz
        if (!$quiz->getOwner() === $user) {
            throw new NotFoundHttpException(message: 'Question not found.');
        }

        // Vérifier si la question appartient au quiz
        if ($question->getQuiz() !== $quiz) {
            throw new AccessDeniedHttpException(message: 'The question does not belong to the quiz.');
        }

        $data = json_decode(
            json: $request->getContent(),
            associative: true
        );

        // Vérifier si le titre est présent
        if (!is_array(value: $data) || !isset($data["title"])) {
            throw new BadRequestHttpException(message: 'Title is required.');
        }
        $question->setTitle(title: $data["title"]);

        // Vérifier si les réponses sont présentes
        if (!empty($data["answers"])) {
            // Supprimer les réponses existantes
            $question->getAnswers()->clear();

            // Ajouter les nouvelles réponses
            foreach ($data["answers"] as $answer) {
                $answerNew = (new Answer())
                    ->setTitle(title: $answer["title"] ?? '')
                    ->setIsCorrect(isCorrect: $answer["isCorrect"] ?? false)
                    ->setQuestion(question: $question);

                $this->entityManager->persist(object: $answerNew);

                $question->addAnswer(answer: $answerNew);
            }
        }

        $this->entityManager->flush();

        return $this->json(
            data: null,
            status: Response::HTTP_NO_CONTENT,
        );
    }

    /**
     * Méthode getQuestion
     * 
     * Router permettant de récupérer une question
     * 
     * @access public
     * @since 1.0.0
     * 
     * @param Request $request Requête
     * @param Quiz $quiz Quiz
     * @param Question $question Question
     * 
     * @return JsonResponse Réponse HTTP
     */
    #[Route(path: '/questions/{question}', name: 'get_one', methods: ['GET'])]
    public function getQuestion(
        Request $request,
        Quiz $quiz,
        Question $question
    ): JsonResponse {
        $user = $this->firebaseAuthService->getUserFromToken(request: $request);

        if ($quiz->getOwner() !== $user) {
            throw new NotFoundHttpException(message: 'Question not found.');
        }

        if ($question->getQuiz() !== $quiz) {
            throw new AccessDeniedHttpException(message: 'The question does not belong to the quiz.');
        }

        $question = $this->normalizer->normalize(
            $question,
            null,
            ['groups' => ['question:read']]
        );

        return $this->json(
            data: $question,
            status: Response::HTTP_OK,
        );
    }
    //#endregion
}
