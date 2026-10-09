<?php
declare(strict_types=1);

namespace App\Model\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use App\Model\UploadKind;
use App\Service\State\Processor\UploadPostProcessor;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;

/**
 * A file uploaded through POST /api/uploads. The row holds its metadata; the bytes live
 * on disk (see UploadStorage) and are served by GET /api/uploads/{id}/content.
 *
 * Upload with multipart/form-data: a "file" part and a "kind" field ("image" or "resume").
 */
#[ORM\Entity]
#[ORM\Index(fields: ['kind'])]
#[ApiResource(
    normalizationContext: ['groups' => ['upload:read']],
)]
#[GetCollection(security: "is_granted('ROLE_ADMIN')")]
#[Get]
#[Post(
    inputFormats: ['multipart' => ['multipart/form-data']],
    security: "is_granted('ROLE_ADMIN')",
    deserialize: false,
    validate: false,
    processor: UploadPostProcessor::class,
)]
#[Delete(security: "is_granted('ROLE_ADMIN')")]
class Upload {
    #[ORM\Id]
    #[ORM\Column(length: 36, options: ['fixed' => true])]
    #[Groups(['upload:read'])]
    public private(set) string $id;

    #[ORM\Column(length: 32, enumType: UploadKind::class)]
    #[Groups(['upload:read'])]
    public private(set) UploadKind $kind;

    /** The file name the client sent, for display and downloads only. */
    #[ORM\Column(length: 255)]
    #[Groups(['upload:read'])]
    public private(set) string $originalName;

    /** Sniffed from the contents when the file was uploaded. */
    #[ORM\Column(length: 127)]
    #[Groups(['upload:read'])]
    public private(set) string $mimeType;

    /** In bytes. */
    #[ORM\Column]
    #[Groups(['upload:read'])]
    public private(set) int $size;

    /** Hex SHA-256 of the contents. */
    #[ORM\Column(length: 64, options: ['fixed' => true])]
    #[Groups(['upload:read'])]
    public private(set) string $sha256;

    #[ORM\Column]
    #[Groups(['upload:read'])]
    public private(set) DateTimeImmutable $createdAt;

    #[Groups(['upload:read'])]
    public string $contentUrl {
        get => '/api/uploads/'.$this->id.'/content';
    }

    public function __construct(UploadKind $kind, string $originalName, string $mimeType, int $size, string $sha256) {
        $this->id = Uuid::v4()->toRfc4122();
        $this->kind = $kind;
        $this->originalName = mb_substr($originalName, 0, 255);
        $this->mimeType = $mimeType;
        $this->size = $size;
        $this->sha256 = $sha256;
        $this->createdAt = new DateTimeImmutable();
    }
}
