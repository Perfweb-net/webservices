<?php
namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use OpenApi\Attributes as OA;

/**
 * Classe PingController
 * @final
 * 
 * Contrôleur de gestion du ping
 * 
 * @package App\Controller
 * @category Controller
 * 
 * @version 1.0.0
 * 
 * @author Pierre SAUGUES <pierre.saugues@ynov.com>
 * @author Valentin FORTIN <valentin.fortin@ynov.com>
 */
#[OA\Tag(name: 'Ping')]
#[Route(path: '/api', name: 'ping_')]
final class PingController extends AbstractController
{
    /**
     * Méthode ping
     * 
     * Permet de vérifier la connexion à 
     * la base de données
     * 
     * @access public
     * @since 1.0.0
     * 
     * @param EntityManagerInterface $entityManager Interface d'entité
     * @param LoggerInterface $logger Interface de journalisation
     * 
     * @return JsonResponse Réponse JSON
     */
    #[OA\Response(
        response: 200, 
        description: 'API is available and database is connected',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(
                    property: 'status', 
                    type: 'string'
                ),
                new OA\Property(
                    property: 'database', 
                    type: 'string'
                )
            ]
        )
    )]
    #[OA\Response(
        response: 500, 
        description: 'API is available but database is not connected'
    )]
    #[Route(path: '/ping', name: 'ping', methods: ['GET'])]
    public function ping(
        EntityManagerInterface $entityManager,
        LoggerInterface $logger
    ): JsonResponse {
        try {
            $connection = $entityManager->getConnection();
            $connection->connect();

            $status = $connection->isConnected() ? 'OK' : 'Partial';
            $databaseStatus = $connection->isConnected() ? 'OK' : 'KO';

            return $this->json(
                data: ['status' => $status, 'database' => $databaseStatus],
                status: Response::HTTP_OK
            );
        } catch (\Exception $e) {
            $logger->error("Database is not available: {$e->getMessage()}");

            return $this->json(
                data: ['status' => 'KO', 'database' => 'KO'],
                status: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
