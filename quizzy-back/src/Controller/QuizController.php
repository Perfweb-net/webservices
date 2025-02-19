<?php
namespace App\Controller;

use App\Entity\Quiz;
use App\Entity\User;
use App\Repository\QuizRepository;
use App\Repository\UserRepository;
use App\Service\FirebaseAuthService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Polyfill\Intl\Icu\Exception\NotImplementedException;

#[Route(path: '/api/quiz', name: 'quiz_')]
class QuizController extends AbstractController
{
  public function __construct(
    private readonly FirebaseAuthService $firebaseAuthService,
    private readonly EntityManagerInterface $entityManager,
    private readonly NormalizerInterface $normalizer,
    private readonly ValidatorInterface $validator,
  ) {}

  #[Route(name: 'create', methods: ['POST'])]
  public function createQuiz(Request $request): JsonResponse 
  {
    $user = $this->firebaseAuthService->getUserFromToken(request: $request);

    $params = json_decode(
      json: $request->getContent(), 
      associative: true
    );

    $title = $params['title'];
    if (!$title) {
      throw new BadRequestHttpException(message: 'Title is required');
    }

    $description = $params['description'];
    if ($description === null) {
      throw new BadRequestHttpException(message: 'Description is required');
    }

    $quiz = new Quiz();
    $quiz->setTitle(title: $title);
    $quiz->setDescription(description: $description);
    $quiz->setOwner(owner: $user);

    $errors = $this->validator->validate(value: $quiz);

    if ($errors->count() > 0) {
      $errorMessages = [];
      foreach ($errors as $error) {
        $errorMessages[] = $error->getPropertyPath() . ' : ' . $error->getMessage();
      }
      
      $this->json(
        data: ['errors' => $errorMessages],
        status: Response::HTTP_BAD_REQUEST
      );
    }

    $this->entityManager->persist(object: $quiz);
    $this->entityManager->flush();

    $quizUrl = $this->generateUrl(
      route: 'quiz_get_one',
      parameters: ['id' => $quiz->getId()], 
      referenceType: UrlGeneratorInterface::ABSOLUTE_URL
    );

    return $this->json(
      data: null,
      headers: ['Location' => $quizUrl],
      status: Response::HTTP_CREATED,
    );
  }

  #[Route(name: 'me_get_all', methods: ['GET'])]
  public function getUserQuizzes(Request $request): JsonResponse
  {
    $user = $this->firebaseAuthService->getUserFromToken(request: $request);

    $quizzes = $this->normalizer->normalize(
      $user->getQuizzes(),
      null,
      ['groups' => 'quiz:read']
    );

    return $this->json(
      data: ['data' => $quizzes],
      status: Response::HTTP_OK
    );
  }

  #[Route(path: '/{id}', name: 'get_one', methods: ['GET'])]
  public function getQuiz(): JsonResponse
  {
    throw new NotImplementedException(message: 'Not implemented');
  }
}
