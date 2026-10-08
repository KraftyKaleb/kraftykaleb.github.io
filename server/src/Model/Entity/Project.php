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
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ApiResource(
    operations: [
        new GetCollection(),
        new Get(),
        new Post(security: "is_granted('ROLE_ADMIN')"),
        new Put(security: "is_granted('ROLE_ADMIN')"),
        new Patch(security: "is_granted('ROLE_ADMIN')"),
        new Delete(security: "is_granted('ROLE_ADMIN')"),
    ],
    normalizationContext: ['groups' => ['project:read']],
    denormalizationContext: ['groups' => ['project:write']],
)]
class Project {
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['project:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    #[Groups(['project:read', 'project:write'])]
    private string $name = '';

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank]
    #[Groups(['project:read', 'project:write'])]
    private string $description = '';

    /** @var list<string> */
    #[ORM\Column(type: Types::JSON)]
    #[Assert\All([
        new Assert\NotBlank(),
        new Assert\Length(max: 64),
    ])]
    #[Groups(['project:read', 'project:write'])]
    private array $tags = [];

    /** @var Collection<int, Link> */
    #[ORM\OneToMany(targetEntity: Link::class, mappedBy: 'project', cascade: ['persist'], orphanRemoval: true)]
    #[Groups(['project:read'])]
    private Collection $links;

    public function __construct() {
        $this->links = new ArrayCollection();
    }

    public function getId(): ?int {
        return $this->id;
    }

    public function getName(): string {
        return $this->name;
    }

    public function setName(string $name): static {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): string {
        return $this->description;
    }

    public function setDescription(string $description): static {
        $this->description = $description;

        return $this;
    }

    /** @return list<string> */
    public function getTags(): array {
        return $this->tags;
    }

    /** @param list<string> $tags */
    public function setTags(array $tags): static {
        $this->tags = array_values($tags);

        return $this;
    }

    /** @return Collection<int, Link> */
    public function getLinks(): Collection {
        return $this->links;
    }

    public function addLink(Link $link): static {
        if (!$this->links->contains($link)) {
            $this->links->add($link);
            $link->setProject($this);
        }

        return $this;
    }

    public function removeLink(Link $link): static {
        $this->links->removeElement($link);

        return $this;
    }
}
