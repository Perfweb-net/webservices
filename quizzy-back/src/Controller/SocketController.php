<?php

namespace App\Controller;

use App\Entity\Execution;
use App\Entity\Question;
use App\Entity\User;
use App\Enum\ExecutionStatus;
use App\Repository\QuestionRepository;
use App\Repository\QuizRepository;
use App\Service\FirebaseAuthService;
use App\Service\MercureService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/executions')]
class SocketController extends AbstractController
{
    public function __construct(
        private readonly HubInterface           $mercureHub,
        private readonly MercureService         $mercureService,
        private readonly EntityManagerInterface $entityManager,
        private readonly QuizRepository         $quizRepository,
        private readonly FirebaseAuthService    $firebaseAuthService,
        private readonly QuestionRepository     $questionRepository,
        private readonly SerializerInterface    $serializer,
    ) {}

    #[Route('/{execution}/host', name: 'execution_host', methods: ['GET'])]
    public function hostExecution(Execution $execution): JsonResponse
    {
        $quiz = $execution->getQuiz();
        $executionId = $execution->getId();
        $quizTitle = $quiz->getTitle();

        $quizData = [
            'quizTitle' => $quizTitle,
        ];

        $statusData = [
            'status' => $execution->getStatus(),
            'participants' => $execution->getParticipants()->count(),
        ];

        $update = new Update(
            topics: ["/executions/$executionId/host"],
            data: json_encode(['event' => 'hostDetails', 'data' => $quizData]),
            private: false
        );
        $this->mercureHub->publish($update);

        $update = new Update(
            topics: ["/executions/$executionId/host"],
            data: json_encode(['event' => 'status', 'data' => $statusData]),
            private: false
        );
        $this->mercureHub->publish($update);

        return new JsonResponse(['message' => 'Stream started for host'], Response::HTTP_OK);
    }

    #[Route('/{execution}/join', name: 'execution_join', methods: ['GET'])]
    public function joinExecution(Execution $execution, Request $request): JsonResponse
    {
        $quiz = $execution->getQuiz();
        $executionId = $execution->getId();
        $quizTitle = $quiz->getTitle();
        $user = $this->firebaseAuthService->getUserFromToken(request: $request);

        $execution->addParticipant($user);
        $execution->setStatus(ExecutionStatus::WAITING);
        $this->entityManager->persist($execution);
        $this->entityManager->flush();

        $joinData = [
            'quizTitle' => $quizTitle,
        ];

        $statusData = [
            'status' => $execution->getStatus(),
            'participants' => $execution->getParticipants()->count(),
        ];

        $update = new Update(
            topics: ["/executions/$executionId"],
            data: json_encode(['event' => 'joinDetails', 'data' => $joinData]),
            private: false
        );
        $this->mercureHub->publish($update);

        $update = new Update(
            topics: ["/executions/$executionId"],
            data: json_encode(['event' => 'status', 'data' => $statusData]),
            private: false
        );
        $this->mercureHub->publish($update);

        $updateHost = new Update(
            topics: ["/executions/$executionId/host"],
            data: json_encode(['event' => 'status', 'data' => $statusData]),
            private: false
        );
        $this->mercureHub->publish($updateHost);

        $joinNotification = [
            'message' => "Un participant vient de rejoindre le quiz !",
        ];
        $updateNotification = new Update(
            topics: ["/executions/$executionId"],
            data: json_encode(['event' => 'joinNotification', 'data' => $joinNotification]),
            private: false
        );
        $this->mercureHub->publish($updateNotification);

        $updateNotificationHost = new Update(
            topics: ["/executions/$executionId/host"],
            data: json_encode(['event' => 'joinNotification', 'data' => $joinNotification]),
            private: false
        );
        $this->mercureHub->publish($updateNotificationHost);

        return new JsonResponse(['message' => 'Stream started for join'], Response::HTTP_OK);
    }

    #[Route('/{execution}/next-question', name: 'execution_next_question', methods: ['GET'])]
    public function nextQuestion(Execution $execution, NormalizerInterface $normalizer): JsonResponse
    {
        $executionId = $execution->getId();
        $quiz = $execution->getQuiz();
        $questions = iterator_to_array($quiz->getQuestions());;
        $currentQuestion = $execution->getQuestion();

        $questionIds = array_map(fn($q) => $q->getId(), $questions);

        $currentIndex = array_search($currentQuestion->getId(), $questionIds, true);

        if ($currentIndex !== false && isset($questions[$currentIndex + 1])) {
            $nextQuestion = $questions[$currentIndex + 1];
            $nextQuestion = $this->questionRepository->find($nextQuestion->getId());
        } else {
            $nextQuestion = null;
        }

        if ($nextQuestion === null) {
            $updateQuestion = new Update(
                topics: ["/executions/$executionId"],
                data: json_encode(['event' => 'nextQuestion', 'data' => "no next question"]),
                private: false
            );
            $this->mercureHub->publish($updateQuestion);
            $execution->setStatus(ExecutionStatus::ENDED);
            $this->entityManager->persist($execution);
            $this->entityManager->flush();
            return new JsonResponse(['message' => 'No next question'], Response::HTTP_OK);
        }
        $nextQuestionData = [
            'question' => $nextQuestion->getTitle(),
            'questionId' => $nextQuestion->getId(),
            'answers' => $normalizer->normalize($nextQuestion->getAnswers(),'json', ['groups' => ['answers:read']]),
        ];

        $statusData = [
            'status' => $execution->getStatus(),
            'participants' => $execution->getParticipants()->count(),
        ];

        $updateQuestion = new Update(
            topics: ["/executions/$executionId"],
            data: json_encode(['event' => 'nextQuestion', 'data' => $nextQuestionData]),
            private: false
        );

        $this->mercureHub->publish($updateQuestion);

        // Publier le statut mis à jour (avec le nombre de participants)
        $updateStatus = new Update(
            topics: ["/executions/$executionId"],
            data: json_encode(['event' => 'status', 'data' => $statusData]),
            private: false
        );
        $this->mercureHub->publish($updateStatus);

        // Notifier l'hôte que la question suivante a été envoyée
        $updateNotificationHost = new Update(
            topics: ["/executions/$executionId/host"],
            data: json_encode(['event' => 'nextQuestionNotification', 'data' => $nextQuestionData]),
            private: false
        );
        $this->mercureHub->publish($updateNotificationHost);

        $questionNotification = [
            'message' => "La question suivante est maintenant disponible !",
        ];
        $updateQuestionNotification = new Update(
            topics: ["/executions/$executionId"],
            data: json_encode(['event' => 'questionNotification', 'data' => $questionNotification]),
            private: false
        );
        $this->mercureHub->publish($updateQuestionNotification);

        $execution->setQuestion($nextQuestion);
        $this->entityManager->persist($execution);
        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Next question broadcasted'], Response::HTTP_OK);
    }
}
