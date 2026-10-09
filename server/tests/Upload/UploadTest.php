<?php
declare(strict_types=1);

namespace App\Tests\Upload;

use App\Model\Entity\Role;
use App\Model\Entity\User;
use App\Service\Security\Authentication\TokenService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class UploadTest extends WebTestCase {
    // A 1x1 transparent PNG.
    private const string PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';
    private const string PDF = "%PDF-1.4\n1 0 obj << /Type /Catalog /Pages 2 0 R >> endobj\n2 0 obj << /Type /Pages /Kids [] /Count 0 >> endobj\ntrailer << /Root 1 0 R >>\n%%EOF\n";

    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;
    private string $uploadDir;
    private string $tmpDir;

    protected function setUp(): void {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);

        $schemaTool = new SchemaTool($this->entityManager);
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);

        $adminRole = new Role('ROLE_ADMIN');
        $this->entityManager->persist($adminRole);
        $admin = new User('admin');
        $admin->roles->add($adminRole);
        $this->entityManager->persist($admin);
        $this->entityManager->persist(new User('guest'));
        $this->entityManager->flush();

        $filesystem = new Filesystem();
        $this->uploadDir = static::getContainer()->getParameter('app.upload_dir');
        $filesystem->remove($this->uploadDir);
        $this->tmpDir = sys_get_temp_dir().'/upload-test-'.bin2hex(random_bytes(4));
        $filesystem->mkdir($this->tmpDir);
    }

    protected function tearDown(): void {
        new Filesystem()->remove([$this->tmpDir, $this->uploadDir]);
        parent::tearDown();
    }

    public function testAdminUploadsAnImageAndAnyoneCanFetchIt(): void {
        $png = base64_decode(self::PNG);
        $upload = $this->upload('admin', 'image', $this->file('photo.png', $png));

        self::assertResponseStatusCodeSame(201);
        self::assertSame('image', $upload['kind']);
        self::assertSame('photo.png', $upload['originalName']);
        self::assertSame('image/png', $upload['mimeType']);
        self::assertSame(strlen($png), $upload['size']);
        self::assertSame(hash('sha256', $png), $upload['sha256']);
        self::assertSame('/api/uploads/'.$upload['id'].'/content', $upload['contentUrl']);

        $this->client->request('GET', $upload['contentUrl']);
        self::assertResponseIsSuccessful();
        $response = $this->client->getResponse();
        self::assertSame('image/png', $response->headers->get('Content-Type'));
        self::assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));
        self::assertStringContainsString('inline', (string) $response->headers->get('Content-Disposition'));
        self::assertSame($png, $this->client->getInternalResponse()->getContent());

        $this->client->request('GET', '/api/uploads/'.$upload['id'], server: ['HTTP_ACCEPT' => 'application/json']);
        self::assertResponseIsSuccessful();
    }

    public function testAdminUploadsAResume(): void {
        $upload = $this->upload('admin', 'resume', $this->file('Kaleb Resume.pdf', self::PDF));

        self::assertResponseStatusCodeSame(201);
        self::assertSame('application/pdf', $upload['mimeType']);
    }

    public function testTheTypeIsSniffedNotTrusted(): void {
        // A PDF renamed to .png and labelled image/png is still a PDF.
        $body = $this->upload('admin', 'image', $this->file('fake.png', self::PDF, 'image/png'));

        self::assertResponseStatusCodeSame(422);
        self::assertSame('file', $body['violations'][0]['propertyPath']);
        $this->assertNothingStored();
    }

    public function testTooLargeFilesAreRejected(): void {
        $this->upload('admin', 'resume', $this->file('big.pdf', self::PDF.str_repeat('%', 10 * 1024 * 1024)));

        self::assertResponseStatusCodeSame(422);
        $this->assertNothingStored();
    }

    public function testKindAndFileAreRequired(): void {
        $body = $this->upload('admin', 'spreadsheet', null);

        self::assertResponseStatusCodeSame(422);
        $paths = array_column($body['violations'], 'propertyPath');
        sort($paths);
        self::assertSame(['file', 'kind'], $paths);
    }

    public function testOnlyAdminsCanUpload(): void {
        $file = $this->file('photo.png', base64_decode(self::PNG));

        $this->client->request('POST', '/api/uploads', ['kind' => 'image'], ['file' => $file]);
        self::assertResponseStatusCodeSame(401);

        $this->upload('guest', 'image', $file);
        self::assertResponseStatusCodeSame(403);
        $this->assertNothingStored();
    }

    public function testDeletingAnUploadRemovesItsFile(): void {
        $upload = $this->upload('admin', 'image', $this->file('photo.png', base64_decode(self::PNG)));
        self::assertResponseStatusCodeSame(201);

        $this->client->request('DELETE', '/api/uploads/'.$upload['id'], server: $this->auth('admin'));
        self::assertResponseStatusCodeSame(204);

        $this->client->request('GET', $upload['contentUrl']);
        self::assertResponseStatusCodeSame(404);
        $this->assertNothingStored();
    }

    /** @return array<string, mixed> */
    private function upload(string $username, string $kind, ?UploadedFile $file): array {
        $this->client->request(
            'POST',
            '/api/uploads',
            ['kind' => $kind],
            $file === null ? [] : ['file' => $file],
            $this->auth($username) + ['HTTP_ACCEPT' => 'application/json'],
        );

        return (array) json_decode((string) $this->client->getResponse()->getContent(), true);
    }

    private function file(string $name, string $contents, ?string $clientMimeType = null): UploadedFile {
        $path = $this->tmpDir.'/'.bin2hex(random_bytes(4));
        file_put_contents($path, $contents);

        return new UploadedFile($path, $name, $clientMimeType, test: true);
    }

    /** @return array<string, string> */
    private function auth(string $username): array {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['username' => $username]);
        $token = static::getContainer()->get(TokenService::class)->issue($user);

        return ['HTTP_AUTHORIZATION' => 'Bearer '.$token->token];
    }

    private function assertNothingStored(): void {
        $files = is_dir($this->uploadDir) ? iterator_to_array(new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->uploadDir, \FilesystemIterator::SKIP_DOTS))) : [];
        self::assertSame([], array_keys($files));
    }
}
