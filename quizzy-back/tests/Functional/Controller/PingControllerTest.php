<?php

namespace App\Tests\Functional\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\DBAL\Connection;
use Exception;
use Psr\Log\LoggerInterface;

/**
 * Classe PingControllerTest
 * @final
 * 
 * Cette classe permet de tester le contrôleur PingController.
 * 
 * @package App\Tests\Functional\Controller
 * @category Functional Tests
 * 
 * @version 1.0.0
 * 
 * @author Valentin FORTIN <valentin.fortin@ynov.com>
 */
final class PingControllerTest extends WebTestCase
{
    //#region Méthodes
    /**
     * Méthode testPingWithDatabaseConnected
     * 
     * Cette méthode permet de tester le ping lorsque la 
     * base de données est connectée.
     * 
     * @access public
     * @since 1.0.0
     * 
     * @return void Ne retourne rien
     */
    public function testPingWithDatabaseConnected(): void
    {
        $client = static::createClient();

        // Effectuer une requête GET sur /api/ping
        $client->request(
            method: 'GET', 
            uri: '/api/ping'
        );

        // Vérifier que la requête retourne un succès HTTP 200
        $this->assertResponseIsSuccessful();

        // Vérifier que le contenu est du JSON bien formaté
        $this->assertJson(
            actualJson: $client->getResponse()->getContent(),
            message: 'Response must be a valid JSON array',
        );

        // Décoder la réponse JSON
        $data = json_decode(
            json: $client->getResponse()->getContent(), 
            associative: true
        );

        // Vérifier que le JSON contient les clés attendues
        $this->assertArrayHasKey(
            key: 'status', 
            array: $data,
            message: 'Expected key "status" in the JSON response',
        );

        $this->assertArrayHasKey(
            key: 'database', 
            array: $data,
            message: 'Expected key "database" in the JSON response',
        );

        // Vérifier que le statut est bien "OK" si la base est connectée
        $this->assertEquals(
            expected: 'OK',
            actual: $data['status'],
            message: "Expected status 'OK', got '{$data['status']}'",
        );

        $this->assertEquals(
            expected: 'OK',
            actual: $data['database'],
            message: "Expected database status 'OK', got '{$data['database']}'",
        );
    }

    /**
     * Méthode testPingWithDatabaseDisconnected
     * 
     * Cette méthode permet de tester le ping lorsque la 
     * base de données est déconnectée.
     * 
     * @access public
     * @since 1.0.0
     * 
     * @return void Ne retourne rien
     */
    public function testPingWithDatabaseDisconnected(): void
    {
        $client = static::createClient();

        // Mock de l'EntityManager pour simuler une base de données déconnectée
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(Connection::class);
        $logger = $this->createMock(LoggerInterface::class);

        $entityManager->method('getConnection')->willReturn($connection);
        $connection->method('connect')->willThrowException(new Exception(message: 'Database error'));

        self::getContainer()->set(EntityManagerInterface::class, $entityManager);
        self::getContainer()->set(LoggerInterface::class, $logger);

        // Envoyer la requête
        $client->request(
            method: 'GET', 
            uri: '/api/ping'
        );

        // Vérifier que la requête retourne un HTTP 500
        $this->assertResponseStatusCodeSame(
            expectedCode: 500,
            message: 'Expected HTTP status 500',
        );

        // Vérifier que la réponse est bien en JSON
        $this->assertJson(actualJson: $client->getResponse()->getContent());

        // Décoder la réponse JSON
        $data = json_decode(
            json: $client->getResponse()->getContent(), 
            associative: true
        );

        // Vérifier que la base de données est bien signalée comme KO
        $this->assertEquals(
            expected: 'KO',
            actual: $data['status'],
            message: "Expected status 'KO', got '{$data['status']}'",
        );
        $this->assertEquals(
            expected: 'KO',
            actual: $data['database'],
            message: "Expected database status 'KO', got '{$data['database']}'",
        );
    }
    //#endregion
}
