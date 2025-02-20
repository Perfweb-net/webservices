<?php

namespace App\Tests\E2E;

use RuntimeException;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Classe PingE2ETest
 * @final
 * 
 * Cette classe permet de tester le point de 
 * terminaison /api/ping.
 * 
 * @package App\Tests\E2E
 * @category End-to-End Tests
 * 
 * @version 1.0.0
 * 
 * @author Valentin FORTIN <valentin.fortin@ynov.com>
 */
final class PingE2ETest extends KernelTestCase
{
    //#region Constantes
    /**
     * Constante BASE_URL
     * 
     * URL de base de l'application
     * 
     * @access private
     * @since 1.0.0
     * 
     * @var string BASE_URL URL de base de l'application
     */
    private const BASE_URL = 'http://localhost:8000';

    /**
     * Constante PING_ENDPOINT
     * 
     * Point de terminaison du ping
     * 
     * @access private
     * @since 1.0.0
     * 
     * @var string PING_ENDPOINT Point de terminaison du ping
     */
    private const PING_ENDPOINT = '/api/ping';
    //#endregion

    //#region Méthodes
    /**
     * Méthode testPingEndpointE2E
     * 
     * Cette méthode permet de tester le point de terminaison
     * /api/ping en utilisant un client HTTP.
     * 
     * @access public
     * @since 1.0.0
     * 
     * @return void Ne retourne rien
     */
    public function testPingEndpointE2E(): void
    {
        $client = HttpClient::create();

        $response = $client->request(
            method: 'GET', 
            url: self::BASE_URL . self::PING_ENDPOINT
        );

        $this->assertEquals(
            expected: 200, 
            actual: $response->getStatusCode(), 
            message: "Expected status code 200, got {$response->getStatusCode()}"
        );

        $json = json_decode(
            json: $response->getContent(), 
            associative: true
        );

        $this->assertIsArray(
            actual: $json, 
            message: 'Response must be a valid JSON array'
        );

        $this->assertArrayHasKey(
            key: 'status',
            array: $json, 
            message: "Missing 'status' key in the response"
        );
        $this->assertArrayHasKey(
            key: 'database', 
            array: $json, 
            message: "Missing 'database' key in the response"
        );

        $this->assertEquals(
            expected: 'OK', 
            actual: $json['status'], 
            message: "Expected status 'OK', got '{$json['status']}'"
        );

        $this->assertEquals(
            expected: 'OK', 
            actual: $json['database'], 
            message: "Expected database status 'OK', got '{$json['database']}'"
        );
    }
    //#endregion
}
