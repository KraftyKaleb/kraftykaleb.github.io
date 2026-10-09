<?php
declare(strict_types=1);

namespace App\Tests\Command;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class SeedSkillsCommandTest extends WebTestCase {
    private KernelBrowser $client;

    protected function setUp(): void {
        $this->client = static::createClient();
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);

        $schemaTool = new SchemaTool($entityManager);
        $metadata = $entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);
    }

    public function testSeededSkillsArePubliclyReadableInOrder(): void {
        $this->seed();

        self::assertSame(
            ['Angular', 'TypeScript', 'RxJS', 'Node', 'Express', 'PostgreSQL', 'Docker', 'CI/CD'],
            array_column($this->getSkills(), 'name'),
        );
    }

    public function testSeedingTwiceLoadsNothingTheSecondTime(): void {
        $this->seed();
        $this->seed();

        self::assertCount(8, $this->getSkills());
    }

    public function testCreatingASkillRequiresAToken(): void {
        $this->client->request(
            'POST',
            '/api/skills',
            server: ['CONTENT_TYPE' => 'application/ld+json', 'HTTP_ACCEPT' => 'application/json'],
            content: json_encode(['name' => 'PHP']),
        );

        self::assertResponseStatusCodeSame(401);
    }

    /** @return list<array<string, mixed>> */
    private function getSkills(): array {
        $this->client->request('GET', '/api/skills', server: ['HTTP_ACCEPT' => 'application/json']);
        self::assertResponseIsSuccessful();

        return json_decode((string) $this->client->getResponse()->getContent(), true);
    }

    private function seed(): void {
        $command = new Application($this->client->getKernel())->find('app:skills:seed');
        $tester = new CommandTester($command);
        $tester->execute([]);
        $tester->assertCommandIsSuccessful();
    }
}
