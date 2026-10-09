<?php
declare(strict_types=1);

namespace App\Tests\Contact;

use App\Model\Entity\ContactMessage;
use App\Model\Entity\ContactReply;
use App\Model\Entity\Role;
use App\Model\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class ContactMessageTest extends WebTestCase {
    private const string PASSWORD = 'correct horse battery staple';

    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        static::getContainer()->get('cache.rate_limiter')->clear();

        $schemaTool = new SchemaTool($this->entityManager);
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);

        $adminRole = new Role('ROLE_ADMIN');
        $this->entityManager->persist($adminRole);
        $admin = new User('admin');
        $admin->password = static::getContainer()->get(UserPasswordHasherInterface::class)->hashPassword($admin, self::PASSWORD);
        $admin->roles->add($adminRole);
        $this->entityManager->persist($admin);
        $this->entityManager->flush();
    }

    public function testAnyoneCanSendAMessageAndBothSidesAreEmailed(): void {
        $this->send(['name' => 'Ada', 'email' => 'ada@example.com', 'subject' => 'Hello', 'message' => 'I like your projects.']);

        self::assertResponseStatusCodeSame(201);
        self::assertCount(1, $this->entityManager->getRepository(ContactMessage::class)->findAll());

        self::assertEmailCount(2);
        [$notification, $confirmation] = self::getMailerMessages();
        self::assertEmailAddressContains($notification, 'To', 'contact@localhost');
        self::assertEmailAddressContains($notification, 'Reply-To', 'ada@example.com');
        self::assertEmailTextBodyContains($notification, 'I like your projects.');
        self::assertEmailAddressContains($confirmation, 'To', 'ada@example.com');
        self::assertEmailTextBodyContains($confirmation, 'Hi Ada,');
    }

    public function testInvalidMessagesAreRejected(): void {
        $this->send(['name' => '', 'email' => 'not-an-email', 'message' => '']);
        self::assertResponseStatusCodeSame(422);

        $this->send(['name' => "Ada\r\nBcc: victim@example.com", 'email' => 'ada@example.com', 'message' => 'Hi']);
        self::assertResponseStatusCodeSame(422);

        self::assertEmailCount(0);
    }

    public function testSendingIsRateLimitedPerClient(): void {
        for ($i = 0; $i < 5; $i++) {
            $this->send(['name' => 'Ada', 'email' => 'ada@example.com', 'message' => 'Message '.$i]);
            self::assertResponseStatusCodeSame(201);
        }

        $this->send(['name' => 'Ada', 'email' => 'ada@example.com', 'message' => 'One too many']);

        self::assertResponseStatusCodeSame(429);
        self::assertResponseHasHeader('Retry-After');
        self::assertCount(5, $this->entityManager->getRepository(ContactMessage::class)->findAll());
    }

    public function testOnlyAdminsCanReadMessages(): void {
        $this->send(['name' => 'Ada', 'email' => 'ada@example.com', 'message' => 'Hi']);

        $this->client->request('GET', '/api/contact_messages');
        self::assertResponseStatusCodeSame(401);

        $this->client->request('GET', '/api/contact_messages', server: $this->adminHeaders());
        self::assertResponseIsSuccessful();
        $messages = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertCount(1, $messages);
        self::assertSame('Ada', $messages[0]['name']);
        self::assertSame([], $messages[0]['replies']);
    }

    public function testAdminCanReplyAndTheReplyIsEmailedAndStored(): void {
        $this->send(['name' => 'Ada', 'email' => 'ada@example.com', 'subject' => 'Hello', 'message' => 'I like your projects.']);
        $id = json_decode((string) $this->client->getResponse()->getContent(), true)['id'];

        $this->client->jsonRequest('POST', '/api/contact_replies', [
            'contactMessage' => '/api/contact_messages/'.$id,
            'body' => 'Thanks, Ada!',
        ], $this->adminHeaders());

        self::assertResponseStatusCodeSame(201);
        $reply = self::getMailerMessage();
        self::assertEmailAddressContains($reply, 'To', 'ada@example.com');
        self::assertEmailHeaderSame($reply, 'Subject', 'Re: Hello');
        self::assertEmailTextBodyContains($reply, 'Thanks, Ada!');
        self::assertEmailTextBodyContains($reply, 'I like your projects.');
        self::assertCount(1, $this->entityManager->getRepository(ContactReply::class)->findAll());

        $this->client->request('GET', '/api/contact_messages/'.$id, server: $this->adminHeaders());
        $message = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertSame('Thanks, Ada!', $message['replies'][0]['body']);
    }

    public function testOnlyAdminsCanReply(): void {
        $this->send(['name' => 'Ada', 'email' => 'ada@example.com', 'message' => 'Hi']);
        $id = json_decode((string) $this->client->getResponse()->getContent(), true)['id'];

        $this->client->jsonRequest('POST', '/api/contact_replies', [
            'contactMessage' => '/api/contact_messages/'.$id,
            'body' => 'Spoofed reply',
        ]);

        self::assertResponseStatusCodeSame(401);
        self::assertCount(0, $this->entityManager->getRepository(ContactReply::class)->findAll());
    }

    public function testAdminCanDeleteAMessage(): void {
        $this->send(['name' => 'Ada', 'email' => 'ada@example.com', 'message' => 'Spam']);
        $id = json_decode((string) $this->client->getResponse()->getContent(), true)['id'];

        $this->client->request('DELETE', '/api/contact_messages/'.$id, server: $this->adminHeaders());

        self::assertResponseStatusCodeSame(204);
        $this->entityManager->clear();
        self::assertNull($this->entityManager->find(ContactMessage::class, $id));
    }

    /** @param array<string, string> $body */
    private function send(array $body): void {
        $this->client->jsonRequest('POST', '/api/contact_messages', $body, ['HTTP_ACCEPT' => 'application/json']);
    }

    /** @return array<string, string> */
    private function adminHeaders(): array {
        $this->client->jsonRequest('PUT', '/api/token', ['username' => 'admin', 'password' => self::PASSWORD]);
        $token = json_decode((string) $this->client->getResponse()->getContent(), true)['token'];

        return ['HTTP_AUTHORIZATION' => 'Bearer '.$token, 'HTTP_ACCEPT' => 'application/json'];
    }
}
