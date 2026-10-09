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
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

/** A section of the projects page, e.g. "Professional Projects". */
#[ORM\Entity]
#[UniqueEntity('name')]
#[ApiResource(
    normalizationContext: ['groups' => ['category:read']],
    denormalizationContext: ['groups' => ['category:write']],
    order: ['ordinal' => 'ASC'],
)]
#[GetCollection]
#[Get]
#[Post(security: "is_granted('ROLE_ADMIN')")]
#[Put(security: "is_granted('ROLE_ADMIN')")]
#[Patch(security: "is_granted('ROLE_ADMIN')")]
#[Delete(security: "is_granted('ROLE_ADMIN')")]
class Category {
    #[ORM\Id]
    #[ORM\Column(length: 36, options: ['fixed' => true])]
    #[Groups(['category:read', 'project:read'])]
    public private(set) string $id;

    #[ORM\Column(length: 255, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    #[Groups(['category:read', 'category:write'])]
    public string $name = '';

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(['category:read', 'category:write'])]
    public string $description = '';

    /** Display order on the projects page, lowest first. */
    #[ORM\Column]
    #[Groups(['category:read', 'category:write'])]
    public int $ordinal = 0;

    public function __construct() {
        $this->id = Uuid::v4()->toRfc4122();
    }
}
