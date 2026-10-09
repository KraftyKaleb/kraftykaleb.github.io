<?php
declare(strict_types=1);

namespace App\Model\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use App\Service\State\Processor\ContactReplyPostProcessor;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * An admin's answer to a contact message. Posting one emails it to the sender.
 */
#[ORM\Entity]
#[ApiResource(
    normalizationContext: ['groups' => ['contact_reply:read']],
    denormalizationContext: ['groups' => ['contact_reply:write']],
    security: "is_granted('ROLE_ADMIN')",
)]
#[Get]
#[Post(processor: ContactReplyPostProcessor::class)]
class ContactReply {
    #[ORM\Id]
    #[ORM\Column(length: 36, options: ['fixed' => true])]
    #[Groups(['contact_reply:read', 'contact_message:read'])]
    public private(set) string $id;

    #[ORM\ManyToOne(targetEntity: ContactMessage::class, inversedBy: 'replies')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    #[Groups(['contact_reply:read', 'contact_reply:write'])]
    public ?ContactMessage $contactMessage = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 10000)]
    #[Groups(['contact_reply:read', 'contact_reply:write', 'contact_message:read'])]
    public string $body = '';

    #[ORM\Column]
    #[Groups(['contact_reply:read', 'contact_message:read'])]
    public private(set) DateTimeImmutable $sentAt;

    public function __construct() {
        $this->id = Uuid::v4()->toRfc4122();
        $this->sentAt = new DateTimeImmutable();
    }
}
