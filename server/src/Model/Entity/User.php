<?php
declare(strict_types=1);

namespace App\Model\Entity;


use App\Service\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: UserRepository::class)]
class User implements UserInterface, PasswordAuthenticatedUserInterface {
    #[ORM\Id]
    #[ORM\Column(length: 36, options: ['fixed' => true])]
    public private(set) string $id;

    #[ORM\Column(length: 180, unique: true)]
    public string $username;

    /** The hashed password. */
    #[ORM\Column]
    public string $password = '';

    /** @var Collection<int, Role> */
    #[ORM\ManyToMany(targetEntity: Role::class)]
    public private(set) Collection $roles;

    public function __construct(string $username) {
        $this->id = Uuid::v4()->toRfc4122();
        $this->username = $username;
        $this->roles = new ArrayCollection();
    }

    public function getUserIdentifier(): string {
        return $this->username;
    }

    public function getPassword(): string {
        return $this->password;
    }

    /** @return list<string> */
    public function getRoles(): array {
        return array_values($this->roles->map(static fn (Role $role) => $role->name)->toArray());
    }
}
