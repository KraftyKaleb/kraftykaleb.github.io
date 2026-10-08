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
    normalizationContext: ['groups' => ['link:read']],
    denormalizationContext: ['groups' => ['link:write']],
)]
class Link {
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['link:read', 'project:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    #[Groups(['link:read', 'link:write', 'project:read'])]
    private string $title = '';

    #[ORM\Column(length: 2048)]
    #[Assert\NotBlank]
    #[Assert\Url(requireTld: true)]
    #[Assert\Length(max: 2048)]
    #[Groups(['link:read', 'link:write', 'project:read'])]
    private string $url = '';

    /** PrimeIcons class, e.g. "pi pi-external-link". */
    #[ORM\Column(length: 64)]
    #[Assert\Length(max: 64)]
    #[Groups(['link:read', 'link:write', 'project:read'])]
    private string $icon = '';

    #[ORM\ManyToOne(targetEntity: Project::class, inversedBy: 'links')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    #[Groups(['link:read', 'link:write'])]
    private ?Project $project = null;

    public function getId(): ?int {
        return $this->id;
    }

    public function getTitle(): string {
        return $this->title;
    }

    public function setTitle(string $title): static {
        $this->title = $title;

        return $this;
    }

    public function getUrl(): string {
        return $this->url;
    }

    public function setUrl(string $url): static {
        $this->url = $url;

        return $this;
    }

    public function getIcon(): string {
        return $this->icon;
    }

    public function setIcon(string $icon): static {
        $this->icon = $icon;

        return $this;
    }

    public function getProject(): ?Project {
        return $this->project;
    }

    public function setProject(?Project $project): static {
        $this->project = $project;

        return $this;
    }
}
