<?php

namespace App\Tests\E2E;

use Exception;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * Classe UserE2ETest
 *
 * Teste les routes du contrôleur UserController.
 *
 * @package App\Tests\E2E
 * @category End-to-End Tests
 * @version 1.0.0
 *
 * @author ANGELA DUTON
 * @author Pierre SAUGES
 */
final class UserE2ETest extends FirebaseTestCase
{
    //#region Constantes
    private const USER_ENDPOINT = '/api/users';
    //#endregion

    //#region Méthodes

    public function setUp(): void
    {
        parent::setUp();
        self::bootKernel();
    }

    /**
     * Teste l'enregistrement d'un utilisateur avec un username valide.
     *
     * @throws TransportExceptionInterface
     */
    public function testUserRegistration(): void
    {
        $username = 'user_test';

        $response = $this->client->request(
            method: 'POST',
            url: self::BASE_URL . self::USER_ENDPOINT,
            options: [
                'json' => ['username' => $username],
                'headers' => ['Authorization' => "Bearer ". FirebaseTestCase::$firebaseToken],
            ]
        );

        $statusCode = $response->getStatusCode();

        if ($statusCode === 201) {
            $content = json_decode(
                json: $response->getContent(throw: false), 
                associative: true
            );
            $this->assertSame(
                expected: 'User registered successfully', 
                actual: $content['message']
            );
        } elseif ($statusCode === 409) {
            $content = json_decode(
                json: $response->getContent(throw: false), 
                associative: true
            );

            $this->assertSame(
                expected: 'User already exists', 
                actual: $content['message']
            );
        } else {
            $this->fail(message: "Unexpected HTTP status code received: $statusCode");
        }
    }

    /**
     * Teste l'enregistrement sans fournir de username.
     */
    public function testRegisterUserWithoutUsername(): void
    {
        $response = $this->client->request(
            method: 'POST',
            url: self::BASE_URL . self::USER_ENDPOINT,
            options: [
                'json' => [],
                'headers' => ['Authorization' => "Bearer ". FirebaseTestCase::$firebaseToken],
            ]
        );

        $this->assertEquals(
            expected: 400, 
            actual: $response->getStatusCode(), 
            message: 'Expected HTTP 400 Bad Request'
        );
    }

    /**
     * Teste l'enregistrement sans token d'authentification.
     */
    public function testRegisterUserWithoutAuthorization(): void
    {
        $response = $this->client->request(
            'POST',
            self::BASE_URL . self::USER_ENDPOINT,
            [
                'json' => ['username' => 'testUserNoAuth'],
            ]
        );

        $this->assertEquals(
            expected: 401, 
            actual: $response->getStatusCode(), 
            message: 'Expected HTTP 401 Unauthorized'
        );
    }

    /**
     * Teste la récupération des données utilisateur avec un token valide.
     */
    public function testGetUserDataSuccessfully(): void
    {
        $response = $this->client->request(
            'GET',
            self::BASE_URL . self::USER_ENDPOINT . "/me",
            ['headers' => ['Authorization' => "Bearer ". FirebaseTestCase::$firebaseToken]]
        );

        $this->assertEquals(200, $response->getStatusCode(), 'Expected HTTP 200 OK');

        $json = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('uid', $json);
        $this->assertArrayHasKey('username', $json);
        $this->assertArrayHasKey('email', $json);
    }

    /**
     * Teste l'accès aux données utilisateur sans token.
     */
    public function testGetUserDataWithoutAuthorization(): void
    {
        $response = $this->client->request(
            'GET',
            self::BASE_URL . self::USER_ENDPOINT . "/me"
        );

        $this->assertEquals(401, $response->getStatusCode(), 'Expected HTTP 401 Unauthorized');
    }

    //#endregion
}
