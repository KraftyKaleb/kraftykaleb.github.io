<?php
declare(strict_types=1);

namespace App\Service\Upload;

use ApiPlatform\Validator\Exception\ValidationException;
use App\Model\Entity\Upload;
use App\Model\UploadKind;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Validates an uploaded file against the rules of its kind and stores it. This is the one
 * entry point for accepting files, so every caller gets the same checks.
 */
final readonly class UploadFactory {
    public function __construct(
        private ValidatorInterface $validator,
        private UploadStorage $storage,
    ) {
    }

    /**
     * @throws ValidationException when the kind or the file is not acceptable
     */
    public function create(mixed $kind, mixed $file): Upload {
        $violations = new ConstraintViolationList();
        $kind = is_string($kind) ? UploadKind::tryFrom($kind) : null;

        $violations->addAll($this->validator->startContext()->atPath('kind')->validate($kind, [
            new Assert\NotNull(message: 'Choose a kind: '.implode(', ', array_column(UploadKind::cases(), 'value')).'.'),
        ])->getViolations());
        $violations->addAll($this->validator->startContext()->atPath('file')->validate($file, [
            new Assert\NotNull(message: 'Attach a file in the "file" field.'),
            new Assert\Type(UploadedFile::class),
            ...($kind?->constraints() ?? []),
        ])->getViolations());

        if ($violations->count() > 0) {
            throw new ValidationException($violations);
        }

        /** @var UploadKind $kind */
        /** @var UploadedFile $file */
        $upload = new Upload(
            $kind,
            $file->getClientOriginalName(),
            (string) $file->getMimeType(),
            (int) $file->getSize(),
            (string) hash_file('sha256', $file->getPathname()),
        );
        $this->storage->store($upload, $file);

        return $upload;
    }
}
