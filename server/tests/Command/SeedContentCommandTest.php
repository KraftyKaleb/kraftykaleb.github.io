<?php
declare(strict_types=1);

namespace App\Tests\Command;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class SeedContentCommandTest extends WebTestCase {
    private KernelBrowser $client;

    protected function setUp(): void {
        $this->client = static::createClient();
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);

        $schemaTool = new SchemaTool($entityManager);
        $metadata = $entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);
    }

    public function testSeededProjectsArePubliclyReadableInOrder(): void {
        $this->seed();

        $this->client->request('GET', '/api/projects', server: ['HTTP_ACCEPT' => 'application/json']);

        self::assertResponseIsSuccessful();
        $projects = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertSame(
            ['UND Platform', 'UND Work Well', 'UND Front-End Commons', 'UNDerground'],
            array_column($projects, 'name'),
        );
        self::assertSame('professional', $projects[0]['category']);
        self::assertSame(
            ['UND', 'TypeScript', 'CSS', 'PHP', 'Angular', 'Symfony', 'MariaDB'],
            array_column($projects[0]['tags'], 'name'),
        );
        self::assertSame('https://apps.und.edu/uit/platform/client/public/', $projects[0]['links'][0]['url']);
        self::assertSame([], $projects[3]['links']);
    }

    public function testSeedingTwiceLoadsNothingTheSecondTime(): void {
        $this->seed();
        $this->seed();

        $this->client->request('GET', '/api/projects', server: ['HTTP_ACCEPT' => 'application/json']);
        self::assertCount(4, json_decode((string) $this->client->getResponse()->getContent(), true));

        $this->client->request('GET', '/api/tags', server: ['HTTP_ACCEPT' => 'application/json']);
        self::assertCount(7, json_decode((string) $this->client->getResponse()->getContent(), true));
    }

    private function seed(): void {
        $command = new Application($this->client->getKernel())->find('app:content:seed');
        $tester = new CommandTester($command);
        $tester->execute([]);
        $tester->assertCommandIsSuccessful();
    }
}
