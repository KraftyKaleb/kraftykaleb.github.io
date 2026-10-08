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
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ApiResource(
    normalizationContext: ['groups' => ['link:read']],
    denormalizationContext: ['groups' => ['link:write']],
)]
#[GetCollection]
#[Get]
#[Post(security: "is_granted('ROLE_ADMIN')")]
#[Put(security: "is_granted('ROLE_ADMIN')")]
#[Patch(security: "is_granted('ROLE_ADMIN')")]
#[Delete(security: "is_granted('ROLE_ADMIN')")]
class Link {
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[Groups(['link:read', 'project:read'])]
    public private(set) Uuid $id;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    #[Groups(['link:read', 'link:write', 'project:read'])]
    public string $title = '';

    #[ORM\Column(length: 2048)]
    #[Assert\NotBlank]
    #[Assert\Url(requireTld: true)]
    #[Assert\Length(max: 2048)]
    #[Groups(['link:read', 'link:write', 'project:read'])]
    public string $url = '';

    /** PrimeIcons class, e.g. "pi pi-external-link". */
    #[ORM\Column(length: 64)]
    #[Assert\Length(max: 64)]
    #[Groups(['link:read', 'link:write', 'project:read'])]
    public string $icon = '';

    #[ORM\ManyToOne(targetEntity: Project::class, inversedBy: 'links')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    #[Groups(['link:read', 'link:write'])]
    public ?Project $project = null;

    public function __construct() {
        $this->id = Uuid::v4();
    }
}
