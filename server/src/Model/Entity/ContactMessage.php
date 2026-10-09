<?php
declare(strict_types=1);

namespace App\Model\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use App\Service\State\Processor\ContactMessagePostProcessor;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * A message sent through the public contact form.
 *
 * Anyone can POST one (rate limited); reading and deleting them is for admins.
 */
#[ORM\Entity]
#[ApiResource(
    normalizationContext: ['groups' => ['contact_message:read']],
    denormalizationContext: ['groups' => ['contact_message:write']],
    order: ['createdAt' => 'DESC'],
)]
#[GetCollection(security: "is_granted('ROLE_ADMIN')")]
#[Get(security: "is_granted('ROLE_ADMIN')")]
#[Post(processor: ContactMessagePostProcessor::class)]
#[Delete(security: "is_granted('ROLE_ADMIN')")]
class ContactMessage {
    #[ORM\Id]
    #[ORM\Column(length: 36, options: ['fixed' => true])]
    #[Groups(['contact_message:read', 'contact_reply:read'])]
    public private(set) string $id;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    #[Assert\Regex(pattern: '/[\r\n]/', match: false, message: 'The name must fit on one line.')]
    #[Groups(['contact_message:read', 'contact_message:write'])]
    public string $name = '';

    #[ORM\Column(length: 180)]
    #[Assert\NotBlank]
    #[Assert\Email]
    #[Assert\Length(max: 180)]
    #[Groups(['contact_message:read', 'contact_message:write'])]
    public string $email = '';

    #[ORM\Column(length: 150)]
    #[Assert\Length(max: 150)]
    #[Assert\Regex(pattern: '/[\r\n]/', match: false, message: 'The subject must fit on one line.')]
    #[Groups(['contact_message:read', 'contact_message:write'])]
    public string $subject = '';

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 5000)]
    #[Groups(['contact_message:read', 'contact_message:write'])]
    public string $message = '';

    #[ORM\Column]
    #[Groups(['contact_message:read'])]
    public private(set) DateTimeImmutable $createdAt;

    /** @var Collection<int, ContactReply> */
    #[ORM\OneToMany(targetEntity: ContactReply::class, mappedBy: 'contactMessage', orphanRemoval: true)]
    #[ORM\OrderBy(['sentAt' => 'ASC'])]
    #[Groups(['contact_message:read'])]
    public private(set) Collection $replies;

    public function __construct() {
        $this->id = Uuid::v4()->toRfc4122();
        $this->createdAt = new DateTimeImmutable();
        $this->replies = new ArrayCollection();
    }
}
