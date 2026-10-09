<?php
declare(strict_types=1);

namespace App\Model\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[UniqueEntity('name')]
#[ApiResource(
    normalizationContext: ['groups' => ['skill:read']],
    denormalizationContext: ['groups' => ['skill:write']],
    order: ['ordinal' => 'ASC'],
)]
#[GetCollection]
#[Get]
#[Post(security: "is_granted('ROLE_ADMIN')")]
#[Put(security: "is_granted('ROLE_ADMIN')")]
#[Patch(security: "is_granted('ROLE_ADMIN')")]
#[Delete(security: "is_granted('ROLE_ADMIN')")]
class Skill {
    #[ORM\Id]
    #[ORM\Column(length: 36, options: ['fixed' => true])]
    #[Groups(['skill:read'])]
    public private(set) string $id;

    #[ORM\Column(length: 64, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 64)]
    #[Groups(['skill:read', 'skill:write'])]
    public string $name = '';

    /** Display order on the Skills page, lowest first. */
    #[ORM\Column]
    #[Groups(['skill:read', 'skill:write'])]
    public int $ordinal = 0;

    public function __construct() {
        $this->id = Uuid::v4()->toRfc4122();
    }
}
