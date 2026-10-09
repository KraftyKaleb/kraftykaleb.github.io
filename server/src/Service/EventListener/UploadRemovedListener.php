<?php
declare(strict_types=1);

namespace App\Service\EventListener;

use App\Model\Entity\Upload;
use App\Service\Upload\UploadStorage;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;

/**
 * Deletes an upload's file once its row is gone, however the row was removed.
 */
#[AsEntityListener(event: Events::postRemove, entity: Upload::class)]
final readonly class UploadRemovedListener {
    public function __construct(
        private UploadStorage $storage,
    ) {
    }

    public function postRemove(Upload $upload): void {
        $this->storage->delete($upload);
    }
}
