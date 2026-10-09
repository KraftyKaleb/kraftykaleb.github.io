<?php
declare(strict_types=1);

namespace App\Model\Entity;

use ApiPlatform\Metadata\ApiProperty;
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

/**
 * A piece of free-form site text that changes often, e.g. the "AI in My Workflow" section on the About page.
 * Looked up by slug: GET /api/text_blocks/ai-workflow.
 */
#[ORM\Entity]
#[UniqueEntity('slug')]
#[ApiResource(
    normalizationContext: ['groups' => ['text_block:read']],
    denormalizationContext: ['groups' => ['text_block:write']],
)]
#[GetCollection]
#[Get]
#[Post(security: "is_granted('ROLE_ADMIN')")]
#[Put(security: "is_granted('ROLE_ADMIN')")]
#[Patch(security: "is_granted('ROLE_ADMIN')")]
#[Delete(security: "is_granted('ROLE_ADMIN')")]
class TextBlock {
    #[ORM\Id]
    #[ORM\Column(length: 36, options: ['fixed' => true])]
    #[ApiProperty(identifier: false)]
    #[Groups(['text_block:read'])]
    public private(set) string $id;

    #[ORM\Column(length: 64, unique: true)]
    #[ApiProperty(identifier: true)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 64)]
    #[Assert\Regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', message: 'Use lowercase letters, digits and dashes.')]
    #[Groups(['text_block:read', 'text_block:write'])]
    public string $slug = '';

    /** Plain text; blank lines separate paragraphs. */
    #[ORM\Column(type: Types::TEXT)]
    #[Groups(['text_block:read', 'text_block:write'])]
    public string $body = '';

    public function __construct() {
        $this->id = Uuid::v4()->toRfc4122();
    }
}
