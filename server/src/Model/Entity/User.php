<?php
declare(strict_types=1);

namespace App\Model\Entity;


use App\Service\Repository\UserRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: UserRepository::class)]
class User implements UserInterface, PasswordAuthenticatedUserInterface {
    public const string ROLE_ADMIN = 'ROLE_ADMIN';

    #[ORM\Id]
    #[ORM\Column(
        length: 36,
        options: [
            'fixed'=> true,
        ]
    )]
    #[ORM\GeneratedValue(strategy: "NONE")]
    private string $id;

    #[ORM\Column(length: 180, unique: true)]
    private string $username;

    #[ORM\Column]
    private string $password = '';

    /** @var list<string> */
    #[ORM\Column(type: Types::JSON)]
    private array $roles = [];

    public function __construct(string $username, array $roles = [self::ROLE_ADMIN]) {
        $this->id = Uuid::v7()->toRfc4122();
        $this->username = $username;
        $this->setRoles($roles);
    }

    public function getId(): string {
        return $this->id;
    }

    public function getUsername(): string {
        return $this->username;
    }

    public function getUserIdentifier(): string {
        return $this->username;
    }

    public function getPassword(): string {
        return $this->password;
    }

    public function setPassword(string $hashedPassword): self {
        $this->password = $hashedPassword;

        return $this;
    }

    /** @return list<string> */
    public function getRoles(): array {
        return array_values(array_unique([...$this->roles, 'ROLE_USER']));
    }

    /** @param list<string> $roles */
    public function setRoles(array $roles): self {
        $this->roles = array_values(array_unique($roles));

        return $this;
    }
}
