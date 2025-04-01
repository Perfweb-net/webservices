<?php

namespace App\Controller;

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
        private readonly HubInterface $mercureHub
    ) {
    }

    #[Route('/{id}/host', name: 'execution_host', methods: ['GET'])]
    public function hostExecution(string $id, MercureService $mercureService): JsonResponse
    {
        $quizTitle = "Quiz for execution $id";
        $quizData = [
            'quizTitle' => $quizTitle,
        ];

        $participantsCount = $mercureService->getParticipantsCount();

        $statusData = [
            'status' => 'waiting',
            'participants' => $participantsCount,
        ];

        $update = new Update(
            topics: ["/executions/$id/host"],
            data: json_encode(['event' => 'hostDetails', 'data' => $quizData]),
            private: false
        );
        $this->mercureHub->publish($update);

        $update = new Update(
            topics: ["/executions/$id/host"],
            data: json_encode(['event' => 'status', 'data' => $statusData]),
            private: false
        );
        $this->mercureHub->publish($update);

        return new JsonResponse(['message' => 'Stream started for host'], Response::HTTP_OK);
    }

    #[Route('/{id}/join', name: 'execution_join', methods: ['GET'])]
    public function joinExecution(string $id, MercureService $mercureService): JsonResponse
    {
        $quizTitle = "Quiz for execution $id"; // À récupérer dynamiquement

        $mercureService->addParticipant($id);

        $joinData = [
            'quizTitle' => $quizTitle,
        ];

        $participantsCount = $mercureService->getParticipantsCount();

        $statusData = [
            'status' => 'waiting',
            'participants' => $participantsCount,
        ];

        $update = new Update(
            topics: ["/executions/$id"],
            data: json_encode(['event' => 'joinDetails', 'data' => $joinData]),
            private: false
        );
        $this->mercureHub->publish($update);

        $update = new Update(
            topics: ["/executions/$id"],
            data: json_encode(['event' => 'status', 'data' => $statusData]),
            private: false
        );
        $this->mercureHub->publish($update);

        $updateHost = new Update(
            topics: ["/executions/$id/host"],
            data: json_encode(['event' => 'status', 'data' => $statusData]),
            private: false
        );
        $this->mercureHub->publish($updateHost);

        $joinNotification = [
            'message' => "Un participant vient de rejoindre le quiz !",
        ];
        $updateNotification = new Update(
            topics: ["/executions/$id"],
            data: json_encode(['event' => 'joinNotification', 'data' => $joinNotification]),
            private: false
        );
        $this->mercureHub->publish($updateNotification);

        $updateNotificationHost = new Update(
            topics: ["/executions/$id/host"],
            data: json_encode(['event' => 'joinNotification', 'data' => $joinNotification]),
            private: false
        );
        $this->mercureHub->publish($updateNotificationHost);

        return new JsonResponse(['message' => 'Stream started for join'], Response::HTTP_OK);
    }
}
