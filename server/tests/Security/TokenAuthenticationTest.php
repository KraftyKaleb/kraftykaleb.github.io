<?php
declare(strict_types=1);

namespace App\Tests\Security;

use App\Model\Entity\Role;
use App\Model\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class TokenAuthenticationTest extends WebTestCase {
    private const string PASSWORD = 'correct horse battery staple';

    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);

        $schemaTool = new SchemaTool($this->entityManager);
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);

        $adminRole = new Role('ROLE_ADMIN');
        $this->entityManager->persist($adminRole);
        $this->createUser('admin')->roles->add($adminRole);
        $this->createUser('guest');
        $this->entityManager->flush();
    }

    public function testAdminCanLogInAndUseTheToken(): void {
        $token = $this->logIn('admin');

        $this->client->request('GET', '/api/token', server: ['HTTP_AUTHORIZATION' => 'Bearer '.$token, 'HTTP_ACCEPT' => 'application/json']);

        self::assertResponseIsSuccessful();
        $current = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertSame($token, $current['token']);
        self::assertSame('admin', $current['username']);
    }

    public function testWrongPasswordIsRejected(): void {
        $this->putToken('admin', 'wrong password');

        self::assertResponseStatusCodeSame(401);
    }

    public function testTokenRequiresTheAdminRole(): void {
        $token = $this->logIn('guest');

        $this->client->request('GET', '/api/token', server: ['HTTP_AUTHORIZATION' => 'Bearer '.$token]);

        self::assertResponseStatusCodeSame(403);
    }

    public function testMissingOrInvalidTokenIsRejected(): void {
        $this->client->request('GET', '/api/token');
        self::assertResponseStatusCodeSame(401);

        $this->client->request('GET', '/api/token', server: ['HTTP_AUTHORIZATION' => 'Bearer not-a-token']);
        self::assertResponseStatusCodeSame(401);
    }

    private function createUser(string $username): User {
        $user = new User($username);
        $user->password = static::getContainer()->get(UserPasswordHasherInterface::class)->hashPassword($user, self::PASSWORD);
        $this->entityManager->persist($user);

        return $user;
    }

    private function logIn(string $username): string {
        $this->putToken($username, self::PASSWORD);
        self::assertResponseIsSuccessful();

        $body = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertIsString($body['token']);
        self::assertIsString($body['expiresAt']);
        self::assertSame($username, $body['username']);

        return $body['token'];
    }

    private function putToken(string $username, string $password): void {
        $this->client->jsonRequest('PUT', '/api/token', ['username' => $username, 'password' => $password]);
    }
}
