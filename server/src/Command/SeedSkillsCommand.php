<?php
declare(strict_types=1);

namespace App\Command;

use App\Model\Entity\Skill;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Loads the skills the client used to hardcode, so the Skills page keeps its content once it
 * reads from the API. Does nothing when any skill already exists.
 */
#[AsCommand(name: 'app:skills:seed', description: 'Loads the initial skills into an empty database.')]
final readonly class SeedSkillsCommand {
    private const array SKILLS = ['Angular', 'TypeScript', 'RxJS', 'Node', 'Express', 'PostgreSQL', 'Docker', 'CI/CD'];

    public function __construct(private EntityManagerInterface $entityManager) {
    }

    public function __invoke(SymfonyStyle $io): int {
        if ($this->entityManager->getRepository(Skill::class)->count() > 0) {
            $io->note('Skills already exist, nothing was loaded.');

            return Command::SUCCESS;
        }

        foreach (self::SKILLS as $ordinal => $name) {
            $skill = new Skill();
            $skill->name = $name;
            $skill->ordinal = $ordinal;
            $this->entityManager->persist($skill);
        }

        $this->entityManager->flush();

        $io->success(sprintf('Loaded %d skills.', count(self::SKILLS)));

        return Command::SUCCESS;
    }
}
