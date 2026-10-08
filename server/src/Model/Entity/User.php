<?php
declare(strict_types=1);

namespace App\Model\Entity;


use App\Service\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Uuid;

/**
 * An admin of the API. Every user is an admin, so there is no roles column.
 */
#[ORM\Entity(repositoryClass: UserRepository::class)]
class User implements UserInterface, PasswordAuthenticatedUserInterface {
    public const string ROLE_ADMIN = 'ROLE_ADMIN';

    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    public private(set) Uuid $id;

    #[ORM\Column(length: 180, unique: true)]
    public string $username;

    /** The hashed password. */
    #[ORM\Column]
    public string $password = '';

    public function __construct(string $username) {
        $this->id = Uuid::v7();
        $this->username = $username;
    }

    public function getUserIdentifier(): string {
        return $this->username;
    }

    public function getPassword(): string {
        return $this->password;
    }

    /** @return list<string> */
    public function getRoles(): array {
        return [self::ROLE_ADMIN];
    }
}
