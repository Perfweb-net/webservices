<?php
namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class PingController extends AbstractController
{
    #[Route('/api/ping', methods: ['GET'])]
    public function ping(): JsonResponse
    {
        return new JsonResponse(['success' => 'pong'], 200);
    }
}
