<?php
declare(strict_types=1);

namespace App\Service\State\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * The "login" firewall answers JSON requests to PUT /token before they get here,
 * so only requests that were not sent as JSON reach this processor.
 *
 * @implements ProcessorInterface<mixed, never>
 */
final class TokenPutProcessor implements ProcessorInterface {
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): never {
        throw new BadRequestHttpException('Send a JSON body with "username" and "password".');
    }
}
