<?php

namespace App\Tests\E2E;

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
        
        // Send a GET request to the actual Symfony server
        $response = $client->request(
            method: 'GET', 
            url: 'http://127.0.0.1:8000/api/ping'
        );

        // Check that the HTTP status is 200
        $this->assertEquals(200, $response->getStatusCode(), 'Expected HTTP status 200, got ' . $response->getStatusCode());

        // Ensure the response is valid JSON
        $json = json_decode(
            json: $response->getContent(), 
            associative: true
        );

        $this->assertIsArray(
            actual: $json, 
            message: 'Response must be a valid JSON array'
        );

        // Verify the JSON contains the expected keys
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

        // Ensure database connection status is 'OK'
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
