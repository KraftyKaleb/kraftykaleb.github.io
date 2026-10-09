<?php
declare(strict_types=1);

namespace App\Service\State\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Model\Entity\Upload;
use App\Service\Upload\UploadFactory;
use App\Service\Upload\UploadStorage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Throwable;

/**
 * Stores the multipart "file" part as the "kind" given in the form.
 *
 * @implements ProcessorInterface<mixed, Upload>
 */
final readonly class UploadPostProcessor implements ProcessorInterface {
    public function __construct(
        private UploadFactory $uploads,
        private UploadStorage $storage,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Upload {
        /** @var Request $request */
        $request = $context['request'];
        $upload = $this->uploads->create($request->request->get('kind'), $request->files->get('file'));

        try {
            $this->entityManager->persist($upload);
            $this->entityManager->flush();
        } catch (Throwable $e) {
            $this->storage->delete($upload);
            throw $e;
        }

        return $upload;
    }
}
