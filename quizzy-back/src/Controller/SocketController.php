<?php

namespace App\Controller;

use App\Entity\Execution;
use App\Entity\User;
use App\Service\MercureService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\HttpFoundation\Response;

#[Route('/api/executions')]
class SocketController extends AbstractController
{
    public function __construct(
        private readonly HubInterface $mercureHub,
        private readonly MercureService $mercureService,
        private readonly EntityManagerInterface $entityManager,
        private readonly QuizRepository $quizRepository,
    ) {
    }

    #[Route('/{id}/host', name: 'execution_host', methods: ['GET'])]
    public function hostExecution(Execution $execution): JsonResponse
    {
        $quiz = $execution->getQuiz();
        $executionId = $execution->getId();
        $quizTitle = $quiz->getTitle();

        $quizData = [
            'quizTitle' => $quizTitle,
        ];

        $participantsCount = $this->mercureService->getParticipantsCount();

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

    #[Route('/{id}/join', name: 'execution_join', methods: ['GET'])]
    public function joinExecution(Execution $execution): JsonResponse
    {
        $quiz = $execution->getQuiz();
        $executionId = $execution->getId();
        $quizTitle = $quiz->getTitle();
        $user = $this->getUser();

        if ($user instanceof User) {
            $execution->addParticipant($user);
        }

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

    #[Route('/{id}/next-question', name: 'execution_next_question', methods: ['GET'])]
    public function nextQuestion(Execution $execution): JsonResponse
    {
        $executionId = $execution->getId();
        $quiz = $execution->getQuiz();
        $questions = $quiz->getQuestions();
        $currentQuestion = $execution->getQuestion();

        $currentIndex = array_search($currentQuestion, $questions, true);

        if ($currentIndex !== false && isset($questions[$currentIndex + 1])) {
            $nextQuestion = $questions[$currentIndex + 1];
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
            return new JsonResponse(['message' => 'No next question'], Response::HTTP_OK);
        }

        $nextQuestionData = [
            'question' => $nextQuestion->getTitle(),
            'questionId' => $nextQuestion->getId(),
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

        return new JsonResponse(['message' => 'Next question broadcasted'], Response::HTTP_OK);
    }
}
