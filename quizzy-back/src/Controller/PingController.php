<?php
namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;

class PingController extends AbstractController
{
    #[Route('/api/ping', methods: ['GET'])]
    public function ping(EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            // Essayer une simple requête SQL
            $connection = $entityManager->getConnection();
            $connection->connect();

            if ($connection->isConnected()) {
                return new JsonResponse(['status' => 'OK', 'database' => "OK"], 200);
            } else {
                return new JsonResponse(['status' => 'Partial', 'database' => "KO"], 200);
            }
        } catch (\Exception $e) {
            return new JsonResponse(['status' => 'KO', 'database' => "KO"], 500);
        }
    }
}
