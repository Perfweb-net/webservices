<?php

namespace App\Tests\E2E;

use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\HttpClient\HttpClient;
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
    protected string $firebaseToken;

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
    protected string $firebaseTestEmail;

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
    protected string $firebaseTestPassword;

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
    protected string $firebaseAPIKey;

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
        $this->firebaseAPIKey = $_ENV['FIREBASE_API_KEY'] ?? '';
        if (empty($this->firebaseAPIKey)) {
            throw new RuntimeException(
                message: "Firebase API Key not found. Check .env file."
            );
        }

        // Vérification des variables d'environnement FIREBASE_TEST_EMAIL
        $this->firebaseTestEmail = $_ENV['FIREBASE_TEST_EMAIL'] ?? '';
        if (empty($this->firebaseTestEmail)) {
            throw new RuntimeException(
                message: "Firebase Test Email not found. Check .env file."
            );
        }

        // Vérification des variables d'environnement FIREBASE_TEST_PASSWORD
        $this->firebaseTestPassword = $_ENV['FIREBASE_TEST_PASSWORD'] ?? '';
        if (empty($this->firebaseTestPassword)) {
            throw new RuntimeException(
                message: "Firebase Test Password not found. Check .env file."
            );
        }

        // Récupérer le token Firebase
        $this->firebaseToken = $this->fetchFirebaseToken();
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
        $response = $this->client->request('POST', "https://identitytoolkit.googleapis.com/v1/accounts:signInWithPassword?key={$this->firebaseAPIKey}", [
            'json' => [
                'email' => $this->firebaseTestEmail,
                'password' => $this->firebaseTestPassword,
                'returnSecureToken' => true
            ],
        ]);

        if ($response->getStatusCode() !== 200) {
            throw new RuntimeException(
                message: "Failed to fetch Firebase token. Check API Key and credentials."
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