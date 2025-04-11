<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Classe FirebaseAuthService
 * @final
 * 
 * Service d'authentification 
 * via Firebase
 * 
 * @package App\Service
 * @category Service
 * 
 * @author Pierre SAUGUES <pierre.saugues@ynov.com>
 * @author Valentin FORTIN <valentin.fortin@ynov.com>
 */
final class FirebaseAuthService
{
  //#region Constantes
  /**
   * Constante PUBLIC_KEY_URL
   * 
   * URL de la clé publique
   * 
   * @access private
   * @since 1.0.0
   * 
   * @var string PUBLIC_KEY_URL
   */
  private const PUBLIC_KEY_URL = 'https://www.googleapis.com/robot/v1/metadata/x509/securetoken@system.gserviceaccount.com';
  //#endregion

  //#region Constructeur
  /**
   * Constructeur
   * 
   * Initialise les dépendances du service
   * 
   * @access public
   * @since 1.0.0
   * 
   * @param HttpClientInterface $httpClient Client HTTP
   * @param UserRepository $userRepository Dépôt d'utilisateurs
   * @param CacheInterface $cache Cache
   */
  public function __construct(
    private readonly HttpClientInterface $httpClient,
    private readonly UserRepository $userRepository,
    private readonly CacheInterface $cache
  ) {
  }
  //#endregion

  //#region Méthodes
  /**
   * Méthode fetchPublicKeys
   * 
   * Récupère les clés publiques
   * 
   * @access private
   * @since 1.0.0
   * 
   * @return array<string, string> Clés publiques
   * 
   * @throws RuntimeException Erreur lors de la récupération des clés publiques
   */
  private function fetchPublicKeys(): array
  {
    try {
      $response = $this->httpClient->request(method: 'GET', url: self::PUBLIC_KEY_URL);
      $statusCode = $response->getStatusCode();
      if ($statusCode !== 200) {
        throw new RuntimeException(message: "Erreur lors de la récupération des clés publiques, code HTTP : $statusCode");
      }
      return $response->toArray();
    } catch (HttpExceptionInterface $exception) {
      throw new RuntimeException(
        message: "Impossible de récupérer les clés publiques: {$exception->getMessage()}",
        previous: $exception
      );
    }
  }

  /**
   * Méthode extractBearerToken
   * 
   * Extrait le token Bearer
   * 
   * @access public
   * @since 1.0.0
   * 
   * @param Request $request Requête
   * 
   * @return string|null Token Bearer
   */
  public function extractBearerToken(Request $request): ?string
  {
    $authHeader = $request->headers->get('Authorization');
    if ($authHeader && preg_match('/^Bearer\s(\S+)$/', $authHeader, $matches)) {
      return $matches[1];
    }
    return null;
  }

  /**
   * Méthode getPublicKeys
   * 
   * Récupère les clés publiques
   * 
   * @access public
   * @since 1.0.0
   * 
   * @return array<string, string> Clés publiques
   */
  public function getPublicKeys(): array
  {
    return $this->cache->get(key: 'firebase_public_keys', callback: function (ItemInterface $item): array {
      $item->expiresAfter(time: 3600);
      return $this->fetchPublicKeys();
    });
  }

  /**
   * Méthode getUserFromToken
   * 
   * Récupère l'utilisateur authentifié
   * 
   * @access public
   * @since 1.0.0
   * 
   * @param Request $request Requête
   * 
   * @return User Utilisateur authentifié
   * 
   * @throws UnauthorizedHttpException Jetée si aucun token valide n'est trouvé
   * @throws NotFoundHttpException Jetée si l'utilisateur n'est pas trouvé
   */
  public function getUserFromToken(Request $request): User {
    $token = $this->extractBearerToken(request: $request);
    if (!$token) {
      throw new UnauthorizedHttpException(
        challenge: 'Bearer',
        message: 'No valid token found'
      );
    }

    $decodedToken = $this->verifyToken(token: $token);
    $userUid = $decodedToken->sub;

    $user = $this->userRepository->find($userUid);
    if (!$user instanceof User) {
      throw new UnauthorizedHttpException(
        challenge: 'Bearer',
        message: 'User not found'
      );
    }

    return $user;
  }

  /**
   * Méthode extractKeyId
   * 
   * Extrait l'identifiant de la clé
   * 
   * @access public
   * @since 1.0.0
   * 
   * @param string $token Token
   * 
   * @return string Identifiant de la clé
   * 
   * @throws InvalidArgumentException Jetée si le format du token est invalide
   * @throws InvalidArgumentException Jetée si l'identifiant de la clé n'est pas trouvé dans l'en-tête du token
   */
  public function extractKeyId(string $token): string
  {
    $segments = explode('.', $token);
    if (count(value: $segments) !== 3) {
      throw new InvalidArgumentException(message: 'Invalid token format');
    }

    $header = json_decode(base64_decode(strtr($segments[0], '-_', '+/')), true);
    if (!isset($header['kid'])) {
      throw new InvalidArgumentException(message: 'Key ID (kid) not found in the token header');
    }

    return $header['kid'];
  }

  /**
   * Méthode verifyToken
   * 
   * Vérifie le token
   * 
   * @access public
   * @since 1.0.0
   * 
   * @param string $token Token
   * 
   * @return object Token vérifié
   * 
   * @throws RuntimeException Jetée si la clé publique n'est pas trouvée pour le kid fourni
   */
  public function verifyToken(string $token): object
  {
    $keys = $this->getPublicKeys();
    $kid = $this->extractKeyId(token: $token);

    if (!isset($keys[$kid])) {
      throw new RuntimeException(message: 'Public key not found for the provided kid');
    }

    return JWT::decode(
      jwt: $token,
      keyOrKeyArray: new Key(
        keyMaterial: $keys[$kid],
        algorithm: 'RS256'
      )
    );
  }
  //#endregion
}