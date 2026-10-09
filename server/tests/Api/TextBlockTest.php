<?php
declare(strict_types=1);

namespace App\Tests\Api;

use App\Model\Entity\Role;
use App\Model\Entity\TextBlock;
use App\Model\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class TextBlockTest extends WebTestCase {
    private const string PASSWORD = 'correct horse battery staple';
    private const string MERGE_PATCH = 'application/merge-patch+json';

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

        $block = new TextBlock();
        $block->slug = 'ai-workflow';
        $block->body = "First paragraph.\n\nSecond paragraph.";
        $this->entityManager->persist($block);
        $this->entityManager->flush();
    }

    public function testAnyoneCanReadABlockBySlug(): void {
        $this->client->request('GET', '/api/text_blocks/ai-workflow', server: ['HTTP_ACCEPT' => 'application/json']);

        self::assertResponseIsSuccessful();
        $block = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertSame('ai-workflow', $block['slug']);
        self::assertSame("First paragraph.\n\nSecond paragraph.", $block['body']);
    }

    public function testUnknownSlugIsNotFound(): void {
        $this->client->request('GET', '/api/text_blocks/nope', server: ['HTTP_ACCEPT' => 'application/json']);

        self::assertResponseStatusCodeSame(404);
    }

    public function testAdminCanEditTheBody(): void {
        $token = $this->logIn('admin');

        $this->patch('/api/text_blocks/ai-workflow', ['body' => 'Updated.'], $token);
        self::assertResponseIsSuccessful();

        $this->client->request('GET', '/api/text_blocks/ai-workflow', server: ['HTTP_ACCEPT' => 'application/json']);
        $block = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertSame('Updated.', $block['body']);
    }

    public function testAdminCanCreateABlock(): void {
        $token = $this->logIn('admin');

        $this->client->request('POST', '/api/text_blocks', server: [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer '.$token,
        ], content: json_encode(['slug' => 'now', 'body' => 'What I am up to.']));

        self::assertResponseStatusCodeSame(201);
        $this->client->request('GET', '/api/text_blocks/now', server: ['HTTP_ACCEPT' => 'application/json']);
        self::assertResponseIsSuccessful();
    }

    public function testAnonymousCannotEdit(): void {
        $this->patch('/api/text_blocks/ai-workflow', ['body' => 'Defaced.'], null);

        self::assertResponseStatusCodeSame(401);
    }

    private function patch(string $uri, array $data, ?string $token): void {
        $server = ['CONTENT_TYPE' => self::MERGE_PATCH, 'HTTP_ACCEPT' => 'application/json'];
        if ($token !== null) {
            $server['HTTP_AUTHORIZATION'] = 'Bearer '.$token;
        }
        $this->client->request('PATCH', $uri, server: $server, content: json_encode($data));
    }

    private function createUser(string $username): User {
        $user = new User($username);
        $user->password = static::getContainer()->get(UserPasswordHasherInterface::class)->hashPassword($user, self::PASSWORD);
        $this->entityManager->persist($user);

        return $user;
    }

    private function logIn(string $username): string {
        $this->client->jsonRequest('PUT', '/api/token', ['username' => $username, 'password' => self::PASSWORD]);
        self::assertResponseIsSuccessful();

        return json_decode((string) $this->client->getResponse()->getContent(), true)['token'];
    }
}
