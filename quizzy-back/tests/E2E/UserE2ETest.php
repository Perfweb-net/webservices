<?php

namespace App\Tests\E2E;

use Symfony\Component\HttpFoundation\Response;

class UserE2ETest extends FirebaseTestCase
{
private ?string $userId = null;

/**
* Test de l'enregistrement d'un utilisateur avec un token Firebase valide
*/
public function testRegisterUserWithValidToken(): void
{

$client = static::createClient();


$this->assertNotEmpty($this->firebaseToken, 'Firebase token is not set.');


$client->request(
'POST',
'/api/users',
[],
[],
['HTTP_Authorization' => "Bearer {$this->firebaseToken}"],
json_encode(['username' => 'TestUser'])
);


$this->assertResponseStatusCodeSame(Response::HTTP_CREATED);


$data = json_decode($client->getResponse()->getContent(), true);
var_dump($data);


$this->assertArrayHasKey('userId', $data, 'User ID is missing from the response.');


$this->userId = $data['userId'] ?? null;


$this->assertNotNull($this->userId, "User ID should be set from the response.");
}

/**
* Test de la suppression de l'utilisateur
*/
public function testDeleteUser(): void
{

$this->assertNotNull($this->userId, "User ID should be set from previous test.");


$client = static::createClient();


$client->request(
'DELETE',
'/api/users/' . $this->userId,
[],
[],
['HTTP_Authorization' => "Bearer {$this->firebaseToken}"]
);
$this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
}
}
