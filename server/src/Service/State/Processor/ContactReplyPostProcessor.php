<?php
declare(strict_types=1);

namespace App\Service\State\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Model\Entity\ContactReply;
use App\Service\Mail\ContactMailer;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

/**
 * Emails an admin's reply to the person who sent the contact message, and stores it
 * only once it was sent, so the stored replies are the ones the sender received.
 *
 * @implements ProcessorInterface<ContactReply, ContactReply>
 */
final readonly class ContactReplyPostProcessor implements ProcessorInterface {
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private ProcessorInterface $persistProcessor,
        private ContactMailer $mailer,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ContactReply {
        try {
            $this->mailer->sendReply($data);
        } catch (TransportExceptionInterface $exception) {
            throw new HttpException(502, 'The reply could not be sent: '.$exception->getMessage(), $exception);
        }

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
}
