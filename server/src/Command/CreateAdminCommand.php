<?php
declare(strict_types=1);

namespace App\Command;

use App\Model\Entity\User;
use App\Service\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(name: 'app:admin:create', description: 'Creates an admin user, or resets the password of an existing one.')]
final readonly class CreateAdminCommand {
    public function __construct(
        private UserRepository $users,
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $hasher,
    ) {
    }

    public function __invoke(SymfonyStyle $io, #[Argument('Login name of the admin')] string $username): int {
        $password = $io->askHidden('Password', static function (?string $value): string {
            if ($value === null || strlen($value) < 12) {
                throw new \RuntimeException('The password must be at least 12 characters long.');
            }

            return $value;
        });

        $user = $this->users->findOneBy(['username' => $username]);
        $created = $user === null;
        $user ??= new User($username);
        $user->setRoles([User::ROLE_ADMIN]);
        $user->setPassword($this->hasher->hashPassword($user, $password));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success(sprintf('%s admin "%s".', $created ? 'Created' : 'Updated', $username));

        return Command::SUCCESS;
    }
}
