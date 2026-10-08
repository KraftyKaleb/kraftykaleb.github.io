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
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ApiResource(
    normalizationContext: ['groups' => ['project:read']],
    denormalizationContext: ['groups' => ['project:write']],
)]
#[GetCollection]
#[Get]
#[Post(security: "is_granted('ROLE_ADMIN')")]
#[Put(security: "is_granted('ROLE_ADMIN')")]
#[Patch(security: "is_granted('ROLE_ADMIN')")]
#[Delete(security: "is_granted('ROLE_ADMIN')")]
class Project {
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[Groups(['project:read'])]
    public private(set) Uuid $id;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    #[Groups(['project:read', 'project:write'])]
    public string $name = '';

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank]
    #[Groups(['project:read', 'project:write'])]
    public string $description = '';

    /** @var Collection<int, Tag> */
    #[ORM\ManyToMany(targetEntity: Tag::class)]
    #[ORM\JoinTable(name: 'project_tag')]
    #[Groups(['project:read', 'project:write'])]
    public Collection $tags {
        set(Collection|array $tags) => is_array($tags) ? new ArrayCollection($tags) : $tags;
    }

    /** @var Collection<int, Link> */
    #[ORM\OneToMany(targetEntity: Link::class, mappedBy: 'project', orphanRemoval: true)]
    #[Groups(['project:read'])]
    public private(set) Collection $links;

    public function __construct() {
        $this->id = Uuid::v4();
        $this->tags = new ArrayCollection();
        $this->links = new ArrayCollection();
    }
}
