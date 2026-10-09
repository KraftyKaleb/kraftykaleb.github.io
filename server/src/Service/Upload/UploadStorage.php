<?php
declare(strict_types=1);

namespace App\Service\Upload;

use App\Model\Entity\Upload;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;

/**
 * Keeps the bytes of each Upload on local disk, outside the public web root. Files are
 * named by the upload's id, never by anything the client sent.
 */
final readonly class UploadStorage {
    private Filesystem $filesystem;

    public function __construct(
        #[Autowire('%app.upload_dir%')]
        private string $directory,
    ) {
        $this->filesystem = new Filesystem();
    }

    public function store(Upload $upload, File $file): void {
        $path = $this->path($upload);
        $this->filesystem->mkdir(dirname($path));
        $file->move(dirname($path), basename($path));
    }

    public function path(Upload $upload): string {
        // Sharded by the id's first two characters so no directory grows too large.
        return $this->directory.'/'.$upload->kind->value.'/'.substr($upload->id, 0, 2).'/'.$upload->id;
    }

    public function delete(Upload $upload): void {
        $this->filesystem->remove($this->path($upload));
    }
}
