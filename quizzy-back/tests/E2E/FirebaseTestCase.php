<?php

namespace App\Tests\E2E;

use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Classe FirebaseTestCase
 * @abstract
 * 
 * Cette classe permet de tester les 
 * fonctionnalités Firebase et d'authentifier
 * les requêtes HTTP.
 * 
 * @package App\Tests\E2E
 * @category End-to-End Tests
 * 
 * @version 1.0.0
 * 
 * @author Valentin FORTIN <valentin.fortin@ynov.com>
 */
abstract class FirebaseTestCase extends KernelTestCase
{
    //#region Constantes
    /**
     * Constante BASE_URL
     * 
     * URL de base de l'application
     * 
     * @access protected
     * @since 1.0.0
     * 
     * @var string BASE_URL URL de base de l'application
     */
    protected const BASE_URL = 'http://localhost:8000';
    //#endregion

    //#region Propriétés
    /**
     * Propriété firebaseToken
     * 
     * Token Firebase
     * 
     * @access protected
     * @since 1.0.0
     * 
     * @var string $firebaseToken Token Firebase
     */
    protected static string $firebaseToken;

    /**
     * Propriété firebaseTestEmail
     * 
     * Email de test Firebase
     * 
     * @access protected
     * @since 1.0.0
     * 
     * @var string $firebaseTestEmail Email de test Firebase
     */
    protected static string $firebaseTestEmail;

    /**
     * Propriété firebaseTestPassword
     * 
     * Mot de passe de test Firebase
     * 
     * @access protected
     * @since 1.0.0
     * 
     * @var string $firebaseTestPassword Mot de passe de test Firebase
     */
    protected static string $firebaseTestPassword;

    /**
     * Propriété firebaseAPIKey
     * 
     * Clé API Firebase
     * 
     * @access protected
     * @since 1.0.0
     * 
     * @var string $firebaseAPIKey Clé API Firebase
     */
    protected static string $firebaseAPIKey;

    /**
     * Propriété client
     * 
     * Client HTTP
     * 
     * @access protected
     * @since 1.0.0
     * 
     * @var HttpClientInterface $client Client HTTP
     */
    protected HttpClientInterface $client;

    /**
     * Propriété userId
     * 
     * ID de l'utilisateur de test
     * 
     * @access protected
     * @since 1.0.0
     * 
     * @var string|null $userId ID de l'utilisateur de test
     */
    protected static ?string $userId = null;
    //#endregion

    //#region Méthodes
    /**
     * Méthode setUp
     * 
     * Cette méthode est exécutée avant chaque test
     * 
     * @access public
     * @since 1.0.0
     * 
     * @return void Ne retourne rien
     */
    public function setUp(): void
    {
        parent::setUp();

        $dotenv = new Dotenv();
        $dotenv->loadEnv(dirname(path: __DIR__, levels: 2) . '/.env.local');

        self::bootKernel();

        $this->client = HttpClient::create();

        // Vérification de la variable d'environnement FIREBASE_API_KEY
        FirebaseTestCase::$firebaseAPIKey = $_ENV['FIREBASE_API_KEY'] ?? '';
        if (empty(FirebaseTestCase::$firebaseAPIKey)) {
            throw new RuntimeException(
                message: "Firebase API Key not found. Check .env file."
            );
        }

        // Générer un email et un mot de passe aléatoires
        $email = uniqid(prefix: 'testuser_', more_entropy: true) . '@example.com';
        FirebaseTestCase::$firebaseTestEmail = $email;

        $password = bin2hex(string: random_bytes(length: 4));
        FirebaseTestCase::$firebaseTestPassword = $password;

        // Étape 1 : Enregistrer l'utilisateur sur Firebase
        FirebaseTestCase::$firebaseToken = $this->registerAndFetchToken();

        // Étape 2 : Enregistrer l'utilisateur dans l'application
        $this->registerUser(username: 'test_user_' . uniqid());
    }

    /**
     * Méthode registerAndFetchToken
     * 
     * Inscrit un utilisateur de test et retourne son token Firebase.
     * 
     * @access protected
     * @since 1.0.1
     * 
     * @return string Token Firebase
     */
    protected function registerAndFetchToken(): string
    {
        $url = "https://identitytoolkit.googleapis.com/v1/accounts:signUp?key=" . FirebaseTestCase::$firebaseAPIKey;

        try {
            $response = $this->client->request(
                method: 'POST',
                url: $url,
                options: [
                    'json' => [
                        'email' => FirebaseTestCase::$firebaseTestEmail,
                        'password' => FirebaseTestCase::$firebaseTestPassword,
                        'returnSecureToken' => true
                    ]
                ]
            );

            if ($response->getStatusCode() !== 200) {
                throw new RuntimeException(
                    message: $response->getContent(throw: false),
                );
            }

            $data = json_decode(
                json: $response->getContent(),
                associative: true
            );

            return $data['idToken'] ?? throw new RuntimeException(
                message: "Firebase token not found in response."
            );
        } catch (HttpExceptionInterface $exception) {
            throw new RuntimeException(
                message: "Error during test user registration: {$exception->getMessage()}",
                previous: $exception
            );
        }
    }

    private function deleteFirebaseUser(string $idToken): void
    {
        $this->client->request(
            method: 'POST',
            url: 'https://identitytoolkit.googleapis.com/v1/accounts:delete?key=' . FirebaseTestCase::$firebaseAPIKey,
            options: [
                'json' => ['idToken' => $idToken],
            ]
        );
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Supprimer l'utilisateur de l'application
        if (FirebaseTestCase::$userId) {
            $this->client->request(
                method: 'DELETE',
                url: self::BASE_URL . '/api/users/' . FirebaseTestCase::$userId,
                options: ['headers' => ['Authorization' => "Bearer " . FirebaseTestCase::$firebaseToken]]
            );
        }

        // Supprimer l'utilisateur de Firebase
        $this->deleteFirebaseUser(FirebaseTestCase::$firebaseToken);
    }

    protected function registerUser(string $username): void
    {
        // Étape 1 : Enregistrer l'utilisateur sur Firebase et récupérer le token
        $firebaseToken = $this->registerAndFetchToken();
    
        // Étape 2 : Enregistrer l'utilisateur dans l'application
        $response = $this->client->request(
            method: 'POST',
            url: self::BASE_URL . '/api/users',
            options: [
                'json' => ['username' => $username],
                'headers' => ['Authorization' => "Bearer $firebaseToken"],
            ]
        );
    
        if ($response->getStatusCode() !== 201) {
            throw new RuntimeException(
                message: "Failed to register user in the application. Status code: " . $response->getStatusCode()
            );
        }
    
        $content = json_decode($response->getContent(), true);
    
        // Stocker les informations utilisateur pour les tests
        FirebaseTestCase::$firebaseToken = $firebaseToken;
        FirebaseTestCase::$userId = $content['id'] ?? null;
    }

    /**
     * Méthode fetchFirebaseToken
     * 
     * Cette méthode permet de récupérer le token Firebase
     * 
     * @access private
     * @since 1.0.0
     * 
     * @return string Token Firebase
     */
    private function fetchFirebaseToken(): string
    {
        $response = $this->client->request(
            method: 'POST', 
            url: "https://identitytoolkit.googleapis.com/v1/accounts:signInWithPassword?key=" . FirebaseTestCase::$firebaseAPIKey, 
            options: [
                'json' => [
                    'email' => FirebaseTestCase::$firebaseTestEmail,
                    'password' => FirebaseTestCase::$firebaseTestPassword,
                    'returnSecureToken' => true
                ],
            ]
        );

        if ($response->getStatusCode() !== 200) {
            throw new RuntimeException(
                message: "Failed to fetch Firebase token. Check API Key and credentials.",
            );
        }

        $data = json_decode(
            json: $response->getContent(),
            associative: true
        );

        return $data['idToken'] ?? throw new RuntimeException(
            message: "Firebase token not found in response."
        );
    }
    //#endregion
}