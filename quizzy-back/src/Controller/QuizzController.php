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
use Symfony\Component\Validator\Validator\ValidatorInterface;

class QuizzController extends AbstractController
{
    public function __construct(
        private readonly FirebaseAuthService $firebaseAuthService,
        private readonly EntityManagerInterface $entityManager
    ) {}

    #[Route('/api/quiz', methods: ['POST'])]
    public function CreateQuiz(Request $request, EntityManagerInterface $entityManager, ValidatorInterface $validator, ): JsonResponse
    {
        $title = $request->query->get('title');
        $description = $request->query->get('description');

        if (!$title || !$description) {
            return new JsonResponse(['error' => 'Missing parameters'], 400);
        }

        $quiz = new Quiz();
        $quiz->setTitle($title);
        $quiz->setDescription($description);

        $errors = $validator->validate($quiz);

        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getPropertyPath() . ' : ' . $error->getMessage();
            }
            return new JsonResponse(['errors' => $errorMessages], 400);
        }

        $entityManager->persist($quiz);
        $entityManager->flush();

        $quizUrl = $this->generateUrl('get_quiz', ['id' => $quiz->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return new JsonResponse(null, 201, ['Location' => $quizUrl]);
    }

    #[Route('/api/quiz', methods: ['GET'])]
    public function GetUserQuiz(Request $request, QuizRepository $quizRepository, UserRepository $userRepository): JsonResponse
    {
        $token = $this->firebaseAuthService->extractBearerToken(request: $request);
        if (!$token) {
            throw new UnauthorizedHttpException(
                challenge: 'Bearer',
                message: 'No valid token found'
            );
        }

        $decodedToken = $this->firebaseAuthService->verifyToken(token: $token);
        $userUid = $decodedToken->sub;

        $quizzes = $userRepository->find($userUid);

        $quizzes = $quizzes->getQuizzes();

        dd($quizzes);
        $formattedQuizzes = [];

        foreach ($quizzes as $quiz) {
            dd("toto");
        }

        dd("die");
        foreach ($quizzes as $quiz) {
            $formattedQuizzes[] = [
                'id' => $quiz->getId(),
                'title' => $quiz->getTitle(),
            ];
        }

        dd($formattedQuizzes);



        return new JsonResponse(['data' => $formattedQuizzes], 201);
    }
}
