<?php
declare(strict_types=1);

namespace App\Tests\Security;

use App\Model\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class TokenAuthenticationTest extends WebTestCase {
    private const string PASSWORD = 'correct horse battery staple';

    private KernelBrowser $client;

    protected function setUp(): void {
        $this->client = static::createClient();
        $container = static::getContainer();

        $entityManager = $container->get(EntityManagerInterface::class);
        $schemaTool = new SchemaTool($entityManager);
        $metadata = $entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);

        $admin = new User('admin');
        $admin->setPassword($container->get(UserPasswordHasherInterface::class)->hashPassword($admin, self::PASSWORD));
        $entityManager->persist($admin);
        $entityManager->flush();
    }

    public function testAdminCanLogInAndUseTheToken(): void {
        $token = $this->logIn('admin', self::PASSWORD);

        $this->client->request('GET', '/admin/me', server: ['HTTP_AUTHORIZATION' => 'Bearer '.$token]);

        self::assertResponseIsSuccessful();
        $me = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertSame('admin', $me['username']);
        self::assertContains(User::ROLE_ADMIN, $me['roles']);
    }

    public function testWrongPasswordIsRejected(): void {
        $this->putToken('admin', 'wrong password');

        self::assertResponseStatusCodeSame(401);
    }

    public function testAdminRoutesRequireAToken(): void {
        $this->client->request('GET', '/admin/me');
        self::assertResponseStatusCodeSame(401);

        $this->client->request('GET', '/admin/me', server: ['HTTP_AUTHORIZATION' => 'Bearer not-a-token']);
        self::assertResponseStatusCodeSame(401);
    }

    private function logIn(string $username, string $password): string {
        $this->putToken($username, $password);
        self::assertResponseIsSuccessful();

        $body = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertIsString($body['token']);
        self::assertIsString($body['expires_at']);

        return $body['token'];
    }

    private function putToken(string $username, string $password): void {
        $this->client->jsonRequest('PUT', '/token', ['username' => $username, 'password' => $password]);
    }
}
