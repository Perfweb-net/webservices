<?php

namespace App\Tests\E2E;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Classe QuizE2ETest
 * @final
 * 
 * Cette classe permet de tester le point de 
 * terminaison /api/quiz.
 * 
 * @package App\Tests\E2E
 * @category End-to-End Tests
 * 
 * @version 1.0.0
 * 
 * @author Valentin FORTIN <valentin.fortin@ynov.com>
 */
final class QuizE2ETest extends FirebaseTestCase
{
    //#region Constantes
    /**
     * Constante QUIZ_ENDPOINT
     * 
     * Point de terminaison des quiz
     * 
     * @access private
     * @since 1.0.0
     * 
     * @var string QUIZ_ENDPOINT Point de terminaison des quiz
     */
    private const QUIZ_ENDPOINT = '/api/quiz';
    //#endregion

    //#region Propriétés
    /**
     * Propriété quizId
     * @static
     * 
     * ID du quiz
     * 
     * @access private
     * @since 1.0.0
     * 
     * @var int|null $quizId ID du quiz
     */     
    private static ?int $quizId = null;

    /**
     * Propriété questionId
     * @static
     * 
     * ID de la question
     * 
     * @access private
     * @since 1.0.0
     * 
     * @var int|null $questionId ID de la question
     */
    private static ?int $questionId = null;
    //#endregion

    //#region Méthodes    
    /**
     * Méthode testCreateQuizSuccessfully
     * 
     * Cette méthode permet de tester la création d'un quiz.
     * 
     * @access public
     * @since 1.0.0
     * 
     * @return void Ne retourne rien
     */
    public function testCreateQuizSuccessfully(): void
    {
        $response = $this->client->request(
            method: 'POST',
            url: self::BASE_URL . self::QUIZ_ENDPOINT,
            options: [
                'json' => [
                    'title' => 'Test Quiz',
                    'description' => 'A simple test quiz'
                ],
                'headers' => ['Authorization' => "Bearer {$this->firebaseToken}"],
            ]
        );

        $this->assertEquals(
            expected: 201,
            actual: $response->getStatusCode(),
            message: "Expected status code 201, got {$response->getStatusCode()}"
        );

        $locationHeader = $response->getHeaders()['location'][0] ?? null;
        $this->assertNotNull(
            actual: $locationHeader,
            message: 'Expected location header to be set'
        );

        $this->assertMatchesRegularExpression(
            pattern: '/\/api\/quiz\/\d+/',
            string: $locationHeader,
            message: "Expected location header to match pattern '/api/quiz/\d+', got '$locationHeader'"
        );

        self::$quizId = $locationHeader ? basename(path: $locationHeader) : null;

        $this->assertNotNull(
            actual: self::$quizId,
            message: 'Quiz ID should be extracted from the location header'
        );
    }

    /**
     * Méthode testCreateQuizWithoutAuthorization
     * 
     * Cette méthode permet de tester la création d'un quiz sans autorisation.
     * 
     * @access public
     * @since 1.0.0
     * 
     * @return void Ne retourne rien
     */
    public function testCreateQuizWithoutAuthorization(): void
    {
        $response = $this->client->request(
            method: 'POST',
            url: self::BASE_URL . self::QUIZ_ENDPOINT,
            options: [
                'json' => [
                    'title' => 'Test Quiz',
                    'description' => 'A simple test quiz'
                ],
            ]
        );

        $this->assertEquals(
            expected: 401,
            actual: $response->getStatusCode(),
            message: "Expected status code 401, got {$response->getStatusCode()}"
        );
    }

    /**
     * Méthode testCreateQuizWithInvalidData
     * 
     * Cette méthode permet de tester la création d'un quiz avec des données invalides.
     * 
     * @access public
     * @since 1.0.0
     * 
     * @return void Ne retourne rien
     */
    public function testCreateQuizWithInvalidData(): void
    {
        $response = $this->client->request(
            method: 'POST',
            url: self::BASE_URL . self::QUIZ_ENDPOINT,
            options: [
                'json' => [
                    'title' => '',
                    'description' => 'A simple test quiz'
                ],
                'headers' => ['Authorization' => "Bearer {$this->firebaseToken}"],
            ]
        );

        $this->assertEquals(
            expected: 400,
            actual: $response->getStatusCode(),
            message: "Expected status code 400, got {$response->getStatusCode()}"
        );
    }

    /**
     * Méthode testGetQuizByIdSuccessfully
     * 
     * Cette méthode permet de tester la récupération d'un quiz par son ID.
     * 
     * @access public
     * @since 1.0.0
     * 
     * @return void Ne retourne rien
     */
    public function testGetQuizByIdSuccessfully(): void
    {
        if (!self::$quizId) {
            $this->markTestSkipped(
                message: 'Quiz ID not set. Skipping test.'
            );
        }

        $response = $this->client->request(
            method: 'GET',
            url: self::BASE_URL . self::QUIZ_ENDPOINT . "/" . self::$quizId,
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
            key: 'title', 
            array: $json, 
            message: "Response must contain 'title'"
        );
    }

    /**
     * Méthode testGetQuizByIdNotFound
     * 
     * Cette méthode permet de tester la récupération d'un quiz par un ID inexistant.
     * 
     * @access public
     * @since 1.0.0
     * 
     * @return void Ne retourne rien
     */
    public function testGetQuizByIdNotFound(): void
    {
        $response = $this->client->request(
            method: 'GET',
            url: self::BASE_URL . self::QUIZ_ENDPOINT . '/00',
            options: [
                'headers' => ['Authorization' => "Bearer {$this->firebaseToken}"],
            ]
        );

        $this->assertEquals(
            expected: 404,
            actual: $response->getStatusCode(),
            message: "Expected HTTP 404 Not Found"
        );
    }

    /**
     * Méthode testGetQuizzesSuccessfully
     * 
     * Cette méthode permet de tester la récupération 
     * de tous les quiz (en admétant qu'il y en a au moins un).
     * 
     * @access public
     * @since 1.0.0
     * 
     * @return void Ne retourne rien
     */
    public function testGetQuizzesSuccessfully(): void
    {
        $response = $this->client->request(
            method: 'GET',
            url: self::BASE_URL . self::QUIZ_ENDPOINT,
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

        $this->assertIsArray(
            actual: $json,
            message: "Response must be an array"
        );

        $this->assertGreaterThan(
            expected: 0,
            actual: count(value: $json),
            message: "Response must contain at least one quiz"
        );
    }

    /**
     * Méthode testUpdateQuizSuccessfully
     * 
     * Cette méthode permet de tester la mise à jour d'un quiz.
     * 
     * @access public
     * @since 1.0.0
     * 
     * @return void Ne retourne rien
     */
    public function testUpdateQuizSuccessfully(): void
    {
        if (!self::$quizId) {
            $this->markTestSkipped(
                message: 'Quiz ID not set. Skipping test.'
            );
        }

        $response = $this->client->request(
            method: 'PATCH',
            url: self::BASE_URL . self::QUIZ_ENDPOINT . "/" . self::$quizId,
            options: [
                'json' => [
                    ['op' => 'replace', 'path' => '/title', 'value' => 'Updated Quiz'],
                ],
                'headers' => ['Authorization' => "Bearer {$this->firebaseToken}"],
            ]
        );

        $this->assertEquals(
            expected: 204,
            actual: $response->getStatusCode(),
            message: "Expected HTTP 204 No Content"
        );
    }

    /**
     * Méthode testUpdateQuizWithInvalidData
     * 
     * Cette méthode permet de tester la mise à jour d'un quiz avec des données invalides.
     * 
     * @access public
     * @since 1.0.0
     * 
     * @return void Ne retourne rien
     */
    public function testUpdateQuizWithInvalidData(): void
    {
        if (!self::$quizId) {
            $this->markTestSkipped(
                message: 'Quiz ID not set. Skipping test.'
            );
        }

        $response = $this->client->request(
            method: 'PATCH',
            url: self::BASE_URL . self::QUIZ_ENDPOINT . "/" . self::$quizId,
            options: [
                'json' => [
                    ['op' => 'replace', 'path' => '/title'],
                ],
                'headers' => ['Authorization' => "Bearer {$this->firebaseToken}"],
            ]
        );

        $this->assertEquals(
            expected: 400,
            actual: $response->getStatusCode(),
            message: "Expected HTTP 400 Bad Request"
        );
    }

    /**
     * Méthode testUpdateQuizWithoutAuthorization
     * 
     * Cette méthode permet de tester la mise à jour d'un quiz sans autorisation.
     * 
     * @access public
     * @since 1.0.0
     * 
     * @return void Ne retourne rien
     */
    public function testUpdateQuizWithoutAuthorization(): void
    {
        if (!self::$quizId) {
            $this->markTestSkipped(
                message: 'Quiz ID not set. Skipping test.'
            );
        }

        $response = $this->client->request(
            method: 'PATCH',
            url: self::BASE_URL . self::QUIZ_ENDPOINT . "/" . self::$quizId,
            options: [
                'json' => [
                    ['op' => 'replace', 'path' => '/title', 'value' => 'Updated Quiz'],
                ],
            ]
        );

        $this->assertEquals(
            expected: 401,
            actual: $response->getStatusCode(),
            message: "Expected HTTP 401 Unauthorized"
        );
    }

    /**
     * Méthode testAddQuestionToQuizSuccessfully
     * 
     * Cette méthode permet de tester l'ajout d'une 
     * question à un quiz.
     * 
     * @access public
     * @since 1.0.0
     * 
     * @return void Ne retourne rien
     */
    public function testAddQuestionToQuizSuccessfully(): void
    {
        if (!self::$quizId) {
            $this->markTestSkipped(
                message: 'Quiz ID not set. Skipping test.'
            );
        }

        $response = $this->client->request(
            method: 'POST',
            url: self::BASE_URL . self::QUIZ_ENDPOINT . "/" . self::$quizId . "/questions",
            options: [
                'json' => [
                    'title' => 'Test Question',
                    'answers' => [
                        ['title' => 'Answer 1', 'isCorrect' => true],
                        ['title' => 'Answer 2', 'isCorrect' => false],
                    ],
                ],
                'headers' => ['Authorization' => "Bearer {$this->firebaseToken}"],
            ]
        );

        $this->assertEquals(
            expected: 201,
            actual: $response->getStatusCode(),
            message: "Expected HTTP 201 Created"
        );

        $locationHeader = $response->getHeaders()['location'][0] ?? null;
        $this->assertNotNull(
            actual: $locationHeader,
            message: 'Expected location header to be set'
        );

        $this->assertMatchesRegularExpression(
            pattern: '/\/api\/quiz\/\d+\/questions\/\d+/',
            string: $locationHeader,
            message: "Expected location header to match pattern '/api/quiz/\d+/questions/\d+', got '$locationHeader'"
        );

        self::$questionId = $locationHeader ? basename(path: $locationHeader) : null;

        $this->assertNotNull(
            actual: self::$questionId,
            message: 'Question ID should be extracted from the location header'
        );
    }

    /**
     * Méthode testAddQuestionToQuizWithInvalidData
     * 
     * Cette méthode permet de tester l'ajout d'une question à un quiz avec des données invalides.
     * 
     * @access public
     * @since 1.0.0
     * 
     * @return void Ne retourne rien
     */
    public function testAddQuestionToQuizWithInvalidData(): void
    {
        if (!self::$quizId) {
            $this->markTestSkipped(
                message: 'Quiz ID not set. Skipping test.'
            );
        }

        $response = $this->client->request(
            method: 'POST',
            url: self::BASE_URL . self::QUIZ_ENDPOINT . "/" . self::$quizId . "/questions",
            options: [
                'json' => [],
                'headers' => ['Authorization' => "Bearer {$this->firebaseToken}"],
            ]
        );

        $this->assertEquals(
            expected: 400,
            actual: $response->getStatusCode(),
            message: "Expected HTTP 400 Bad Request"
        );
    }

    /**
     * Méthode testAddQuestionToQuizWithoutAuthorization
     * 
     * Cette méthode permet de tester l'ajout d'une question à un quiz sans autorisation.
     * 
     * @access public
     * @since 1.0.0
     * 
     * @return void Ne retourne rien
     */
    public function testAddQuestionToQuizWithoutAuthorization(): void
    {
        if (!self::$quizId) {
            $this->markTestSkipped(
                message: 'Quiz ID not set. Skipping test.'
            );
        }

        $response = $this->client->request(
            method: 'POST',
            url: self::BASE_URL . self::QUIZ_ENDPOINT . "/" . self::$quizId . "/questions",
            options: [
                'json' => [
                    'title' => 'Test Question',
                    'answers' => [
                        ['title' => 'Answer 1', 'isCorrect' => true],
                        ['title' => 'Answer 2', 'isCorrect' => false],
                    ],
                ],
            ]
        );

        $this->assertEquals(
            expected: 401,
            actual: $response->getStatusCode(),
            message: "Expected HTTP 401 Unauthorized"
        );
    }

    /**
     * Méthode testGetQuestionByIdSuccessfully
     * 
     * Cette méthode permet de tester la récupération d'une question par son ID.
     * 
     * @access public
     * @since 1.0.0
     * 
     * @return void Ne retourne rien
     */
    public function testGetQuestionByIdSuccessfully(): void
    {
        if (!self::$quizId || !self::$questionId) {
            $this->markTestSkipped(
                message: 'Quiz ID or Question ID not set. Skipping test.'
            );
        }

        $response = $this->client->request(
            method: 'GET',
            url: self::BASE_URL . self::QUIZ_ENDPOINT . "/" . self::$quizId . "/questions/" . self::$questionId,
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
            key: 'title',
            array: $json,
            message: "Response must contain 'title'"
        );
    }

    /**
     * Méthode testGetQuestionByIdNotFound
     * 
     * Cette méthode permet de tester la récupération d'une question par un ID inexistant.
     * 
     * @access public
     * @since 1.0.0
     * 
     * @return void Ne retourne rien
     */
    public function testGetQuestionByIdNotFound(): void
    {
        if (!self::$quizId) {
            $this->markTestSkipped(
                message: 'Quiz ID not set. Skipping test.'
            );
        }

        $response = $this->client->request(
            method: 'GET',
            url: self::BASE_URL . self::QUIZ_ENDPOINT . "/" . self::$quizId . "/questions/00",
            options: [
                'headers' => ['Authorization' => "Bearer {$this->firebaseToken}"],
            ]
        );

        $this->assertEquals(
            expected: 404,
            actual: $response->getStatusCode(),
            message: "Expected HTTP 404 Not Found"
        );
    }

    /**
     * Méthode testGetQuestionByIdWithoutAuthorization
     * 
     * Cette méthode permet de tester la récupération d'une question par son ID sans autorisation.
     * 
     * @access public
     * @since 1.0.0
     * 
     * @return void Ne retourne rien
     */
    public function testGetQuestionByIdWithoutAuthorization(): void
    {
        if (!self::$quizId || !self::$questionId) {
            $this->markTestSkipped(
                message: 'Quiz ID or Question ID not set. Skipping test.'
            );
        }

        $response = $this->client->request(
            method: 'GET',
            url: self::BASE_URL . self::QUIZ_ENDPOINT . "/" . self::$quizId . "/questions/" . self::$questionId,
        );

        $this->assertEquals(
            expected: 401,
            actual: $response->getStatusCode(),
            message: "Expected HTTP 401 Unauthorized"
        );
    }

    /**
     * Méthode testReplaceQuestionSuccessfully
     * 
     * Cette méthode permet de tester le remplacement 
     * d'une question par une autre question.
     * 
     * @access public
     * @since 1.0.0
     * 
     * @return void Ne retourne rien
     */
    public function testReplaceQuestionSuccessfully(): void
    {
        if (!self::$quizId || !self::$questionId) {
            $this->markTestSkipped(
                message: 'Quiz ID or Question ID not set. Skipping test.'
            );
        }

        $response = $this->client->request(
            method: 'PUT',
            url: self::BASE_URL . self::QUIZ_ENDPOINT . "/" . self::$quizId . "/questions/" . self::$questionId,
            options: [
                'json' => [
                    'title' => 'Updated Question',
                    'answers' => [
                        ['title' => 'Answer 1', 'isCorrect' => true],
                        ['title' => 'Answer 2', 'isCorrect' => false],
                    ],
                ],
                'headers' => ['Authorization' => "Bearer {$this->firebaseToken}"],
            ]
        );

        $this->assertEquals(
            expected: 204,
            actual: $response->getStatusCode(),
            message: "Expected HTTP 204 No Content"
        );
    }

    /**
     * Méthode testReplaceQuestionWithInvalidData
     * 
     * Cette méthode permet de tester le remplacement d'une question par une autre question avec des données invalides.
     * 
     * @access public
     * @since 1.0.0
     * 
     * @return void Ne retourne rien
     */
    public function testReplaceQuestionWithInvalidData(): void
    {
        if (!self::$quizId || !self::$questionId) {
            $this->markTestSkipped(
                message: 'Quiz ID or Question ID not set. Skipping test.'
            );
        }

        $response = $this->client->request(
            method: 'PUT',
            url: self::BASE_URL . self::QUIZ_ENDPOINT . "/" . self::$quizId . "/questions/" . self::$questionId,
            options: [
                'json' => [],
                'headers' => ['Authorization' => "Bearer {$this->firebaseToken}"],
            ]
        );

        $this->assertEquals(
            expected: 400,
            actual: $response->getStatusCode(),
            message: "Expected HTTP 400 Bad Request"
        );
    }

    /**
     * Méthode testReplaceQuestionWithoutAuthorization
     * 
     * Cette méthode permet de tester le remplacement d'une question par une autre question sans autorisation.
     * 
     * @access public
     * @since 1.0.0
     * 
     * @return void Ne retourne rien
     */
    public function testReplaceQuestionWithoutAuthorization(): void
    {
        if (!self::$quizId || !self::$questionId) {
            $this->markTestSkipped(
                message: 'Quiz ID or Question ID not set. Skipping test.'
            );
        }

        $response = $this->client->request(
            method: 'PUT',
            url: self::BASE_URL . self::QUIZ_ENDPOINT . "/" . self::$quizId . "/questions/" . self::$questionId,
            options: [
                'json' => [
                    'title' => 'Updated Question',
                    'answers' => [
                        ['title' => 'Answer 1', 'isCorrect' => true],
                        ['title' => 'Answer 2', 'isCorrect' => false],
                    ],
                ],
            ]
        );

        $this->assertEquals(
            expected: 401,
            actual: $response->getStatusCode(),
            message: "Expected HTTP 401 Unauthorized"
        );
    }
    //#endregion
}
