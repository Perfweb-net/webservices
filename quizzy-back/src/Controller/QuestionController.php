<?php

namespace App\Controller;

use App\Entity\Answer;
use App\Entity\Question;
use App\Entity\Quiz;
use App\Entity\User;
use App\Repository\AnswerRepository;
use App\Repository\QuestionRepository;
use App\Repository\QuizRepository;
use App\Repository\UserRepository;
use App\Service\FirebaseAuthService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Polyfill\Intl\Icu\Exception\NotImplementedException;

#[Route(path: '/api/quiz/{id}', name: 'questions_')]
class QuestionController extends AbstractController
{
    public function __construct(
        private readonly FirebaseAuthService    $firebaseAuthService,
        private readonly EntityManagerInterface $entityManager,
        private readonly NormalizerInterface    $normalizer,
        private readonly ValidatorInterface     $validator,
        private readonly QuestionRepository     $questionRepository,
        private readonly AnswerRepository       $answerRepository,
    ) {}

    #[Route(path: '/questions', name: 'add', methods: ["POST"])]
    public function addQuestions(Request $request, int $id): JsonResponse
    {
        $user = $this->firebaseAuthService->getUserFromToken($request);
        $quizzes = $user->getQuizzes();
        $data = json_decode($request->getContent(), true);

        if (is_array($data) && isset($data["title"])) {
            foreach ($quizzes as $quiz) {
                if ($quiz->getId() === $id) {
                    $quesstion = new Question();
                    $quesstion->setTitle($data["title"]);
                    $quesstion->setQuiz($quiz);

                    if (isset($data["answers"])) {
                        foreach ($data["answers"] as $answer) {
                            $answerNew = new Answer();
                            if (isset($answer["isCorrect"])) {
                                $answerNew->setIsCorrect($answer["isCorrect"]);
                            }
                            if (isset($answer["title"])) {
                                $answerNew->setTitle($answer["title"]);
                            }
                            $answerNew->setQuestion($quesstion);
                            $this->entityManager->persist($answerNew);
                            $quesstion->addAnswer($answerNew);
                        }
                    }
                    $this->entityManager->persist($quesstion);
                    $this->entityManager->flush();

                    $quesstionUrl = $this->generateUrl(
                        route: 'questions_get_one',
                        parameters: ['id' => $id, 'question_id' => $quesstion->getId()],
                        referenceType: UrlGeneratorInterface::ABSOLUTE_URL
                    );

                    return $this->json(
                        data: null,
                        headers: ['Location' => $quesstionUrl],
                        status: Response::HTTP_CREATED,
                    );
                }
            }
        }

        return new JsonResponse(null, Response::HTTP_NOT_FOUND);
    }

    #[Route(path: '/questions/{question_id}', name: 'edit', methods: ["PUT"])]
    public function editQuestion(Request $request, int $id, int $question_id): JsonResponse
    {
        $user = $this->firebaseAuthService->getUserFromToken($request);
        $quizzes = $user->getQuizzes();
        $data = json_decode($request->getContent(), true);
        if (is_array($data) && isset($data["title"])) {
            foreach ($quizzes as $quiz) {
                if ($quiz->getId() === $id) {
                    $quesstion = $this->questionRepository->findOneById($question_id);
                    $quesstion->setTitle($data["title"]);
                    $quesstion->setQuiz($quiz);

                    if (isset($data["answers"])) {
                        $answersNew = [];
                        foreach ($data["answers"] as $answer) {
                            $answerNew = new Answer();

                            if (isset($answer["isCorrect"])) {
                                $answerNew->setIsCorrect($answer["isCorrect"]);
                            }
                            if (isset($answer["title"])) {
                                $answerNew->setTitle($answer["title"]);
                            }
                            $answerNew->setQuestion($quesstion);
                            $this->entityManager->persist($answerNew);

                            $answersNew[] = $answerNew;
                        }
                        $quesstion->setAnswers($answersNew);
                    }
                    $this->entityManager->persist($quesstion);
                    $this->entityManager->flush();

                    $quesstionUrl = $this->generateUrl(
                        route: 'questions_get_one',
                        parameters: ['id' => $id, 'question_id' => $quesstion->getId()],
                        referenceType: UrlGeneratorInterface::ABSOLUTE_URL
                    );

                    return new JsonResponse(null, $request->isMethod("POST") ? Response::HTTP_CREATED : Response::HTTP_NO_CONTENT, ['Location' => $quesstionUrl]);
                }
            }
        }

        return new JsonResponse(null, Response::HTTP_NOT_FOUND);
    }

    #[Route(path: '/questions/{question_id}', name: 'get_one', methods: ['GET'])]
    public function getQuestion(Request $request, int $id, int $question_id): JsonResponse
    {
        $user = $this->firebaseAuthService->getUserFromToken($request);
        $quizzes = $user->getQuizzes();
        foreach ($quizzes as $quiz) {
            if ($quiz->getId() === $id) {
                $questions = $quiz->getQuestions();

                foreach ($questions as $question) {
                    if ($question->getId() === $question_id) {

                        $questionData = $this->normalizer->normalize(
                            $question,
                            null,
                            ['groups' => ['question:read', 'answer:read']]
                        );

                        $questionData['answers'] = $questionData['answers'] ?? [];
                        return $this->json($questionData, Response::HTTP_OK);
                    }
                }
            }
        }

        throw new NotFoundHttpException("Question non trouvé.");
    }
}
