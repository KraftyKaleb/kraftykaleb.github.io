<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

/**
 * A security role such as ROLE_ADMIN that users can be granted.
 */
#[ORM\Entity]
class Role {
    public const string ADMIN = 'ROLE_ADMIN';

    #[ORM\Id]
    #[ORM\Column(length: 36, options: ['fixed' => true])]
    public private(set) string $id;

    #[ORM\Column(length: 64, unique: true)]
    public private(set) string $name;

    public function __construct(string $name) {
        $this->id = Uuid::v4()->toRfc4122();
        $this->name = $name;
    }
}
