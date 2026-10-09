<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Entity\Upload;
use App\Service\Upload\UploadStorage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Serves an upload's bytes. Public, like the rest of the read API, so the site can show
 * images and link to resumes directly.
 */
#[AsController]
final readonly class UploadContentController {
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UploadStorage $storage,
    ) {
    }

    #[Route('/api/uploads/{id}/content', name: 'upload_content', requirements: ['id' => '[0-9a-f-]{36}'], methods: ['GET', 'HEAD'])]
    public function __invoke(string $id): BinaryFileResponse {
        $upload = $this->entityManager->find(Upload::class, $id);
        $path = $upload === null ? null : $this->storage->path($upload);
        if ($upload === null || !is_file($path)) {
            throw new NotFoundHttpException();
        }

        $response = new BinaryFileResponse($path, headers: [
            'Content-Type' => $upload->mimeType,
            'X-Content-Type-Options' => 'nosniff',
        ], autoEtag: false);
        if (str_starts_with($upload->mimeType, 'image/')) {
            // An image opened on its own should never run anything. (Browsers refuse to
            // show sandboxed PDFs, so PDFs rely on nosniff and their sniffed type.)
            $response->headers->set('Content-Security-Policy', "default-src 'none'; img-src 'self'; style-src 'unsafe-inline'; sandbox");
        }
        $response->setEtag($upload->sha256);
        // Content never changes for a given id; replacing a file means a new upload.
        $response->setPublic();
        $response->setMaxAge(31536000);
        $response->setImmutable();
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_INLINE,
            $upload->originalName,
            $this->asciiFallback($upload->originalName),
        );

        return $response;
    }

    private function asciiFallback(string $name): string {
        $ascii = preg_replace('/[^\x20-\x7e]|[%\/\\\\"]/', '_', $name);

        return $ascii === '' || $ascii === null ? 'file' : $ascii;
    }
}
