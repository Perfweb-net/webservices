<?php

namespace App\Controller;

use App\Entity\Answer;
use App\Entity\Question;
use App\Entity\Quiz;
use App\Entity\User;
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
class AnswerController extends AbstractController
{
    public function __construct(
        private readonly FirebaseAuthService    $firebaseAuthService,
        private readonly EntityManagerInterface $entityManager,
        private readonly NormalizerInterface    $normalizer,
        private readonly ValidatorInterface     $validator,
    )
    {
    }

    #[Route(path: '/questions', name: 'add', methods: ['POST'])]
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
                        parameters: ['id'=> $id,'question_id' => $quesstion->getId()],
                        referenceType: UrlGeneratorInterface::ABSOLUTE_URL
                    );

                    return new JsonResponse(null, Response::HTTP_CREATED, ['Location' => $quesstionUrl]);
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
                            ['groups' => ['question:read', 'answer:read', 'quiz:read']]
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
