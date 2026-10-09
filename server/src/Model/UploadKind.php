<?php
declare(strict_types=1);

namespace App\Model;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * What an uploaded file is for. Each kind decides which files it accepts, so supporting
 * a new kind of upload means adding a case here.
 */
enum UploadKind: string {
    case Image = 'image';
    case Resume = 'resume';

    /**
     * Constraints an uploaded file must pass to be stored as this kind. MIME types are
     * sniffed from the file's contents, never taken from the client.
     *
     * @return list<Constraint>
     */
    public function constraints(): array {
        return match ($this) {
            // SVG is left out on purpose: it can carry scripts.
            self::Image => [new Assert\Image(
                maxSize: '5M',
                mimeTypes: ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
                mimeTypesMessage: 'Upload a JPEG, PNG, WebP or GIF image.',
                maxWidth: 8000,
                maxHeight: 8000,
                detectCorrupted: false,
            )],
            self::Resume => [new Assert\File(
                maxSize: '10M',
                mimeTypes: ['application/pdf'],
                mimeTypesMessage: 'Upload a PDF.',
            )],
        };
    }
}
