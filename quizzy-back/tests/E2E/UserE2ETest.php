<?php

namespace App\Tests\E2E;

use App\Entity\User;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * Classe UserE2ETest
 * @final
 *
 * Cette classe permet de tester le
 * contrôleur UserController.
 *
 * @package App\Tests\E2E
 * @category End-to-End Tests
 *
 * @version 1.0.0
 *
 * @author ANGELA DUTON
 * @author Pierre SAUGES <valentin.fortin@ynov.com>
 */
final class UserE2ETest extends FirebaseTestCase
{
//#region Constantes
    /**
     * Constante USER_ENDPOINT
     *
     * Chemin de l'API pour les
     * utilisateurs
     *
     * @access private
     * @since 1.0.0
     *
     * @var string USER_ENDPOINT Chemin de l'API pour les utilisateurs
     */
    private const USER_ENDPOINT = '/api/users';
//#endregion

//#region Propriétés
    /**
     * Propriété userId
     *
     * Identifiant de l'utilisateur
     *
     * @access private
     * @since 1.0.0
     *
     * @var string|null $userId Identifiant de l'utilisateur
     */
    private static ?string $userId = null;
//#endregion

//#region Méthodes
    private $entityManager;
    private $firebaseAuthService;

    /**
     * Méthode testRegisterUser
     *
     * Cette méthode permet de tester l'inscription
     * d'un utilisateur.
     *
     * @access public
     * @return void Ne retourne rien
     * @throws TransportExceptionInterface
     * @since 1.0.0
     *
     */

    public function testRegisterUserSuccessfully(): void
    {

        // récuperer l'UID
        $decodedToken = $this->firebaseAuthService->verifyToken($this->firebaseToken);
        $userId = $decodedToken->sub;

        // Verifier si le user existe déja
        $repository = $this->entityManager->getRepository(className: User::class);
        $existingUser = $repository->findOneBy(criteria: ['uid' => $userId]);
        $this->assertNull($existingUser, 'User already exists before registration');


        $response = $this->client->request(
            method: 'POST',
            url: self::BASE_URL . self::USER_ENDPOINT,
            options: [
                'json' => ['username' => 'newuser'],
                'headers' => ['Authorization' => "Bearer {$this->firebaseToken}"],
            ]
        );


        $this->assertEquals(
            201,
            $response->getStatusCode());

        $json = json_decode($response->getContent(), true);

        $this->assertEquals(
            'User registered successfully',
            $json['message']);

    }

    public function testGetUserDataSuccessfully(): void
    {
        $response = $this->client->request(
            method: 'GET',
            url: self::BASE_URL . self::USER_ENDPOINT . "/me",
            options: [
                'headers' => ['Authorization' => "Bearer {$this->firebaseToken}"],
            ]
        );

        $this->assertEquals(
            expected: 200,
            actual: $response->getStatusCode(),
            message: "Expected HTTP 200 OK"
        );

        $json = json_decode(
            json: $response->getContent(),
            associative: true
        );

        $this->assertArrayHasKey(
            key: 'uid',
            array: $json,
            message: "Response must contain 'uid'"
        );

        $this->assertArrayHasKey(
            key: 'username',
            array: $json,
            message: "Response must contain 'username'"
        );

        $this->assertArrayHasKey(
            key: 'email',
            array: $json,
            message: "Response must contain 'email'"
        );
    }

    public function testGetUserDataWithoutAuthorization(): void
    {
        $response = $this->client->request(
            method: 'GET',
            url: self::BASE_URL . self::USER_ENDPOINT . "/me",
        );

        $this->assertEquals(
            expected: 401,
            actual: $response->getStatusCode(),
            message: "Expected HTTP 401 Unauthorized"
        );
    }

}
