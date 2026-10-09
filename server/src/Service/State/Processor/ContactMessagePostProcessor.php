<?php
declare(strict_types=1);

namespace App\Service\State\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Model\Entity\ContactMessage;
use App\Service\Mail\ContactMailer;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;

/**
 * Stores a contact form submission, then emails a confirmation to the sender and a
 * notification to the inbox. Submissions are rate limited per client and in total.
 *
 * @implements ProcessorInterface<ContactMessage, ContactMessage>
 */
final readonly class ContactMessagePostProcessor implements ProcessorInterface {
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private ProcessorInterface $persistProcessor,
        private RateLimiterFactoryInterface $contactPerClientLimiter,
        private RateLimiterFactoryInterface $contactGlobalLimiter,
        private ContactMailer $mailer,
        private LoggerInterface $logger,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ContactMessage {
        $request = $context['request'] ?? null;
        $client = $request instanceof Request ? ($request->getClientIp() ?? 'unknown') : 'unknown';

        // The global limit is only spent once the client's own limit lets the message through.
        foreach ([$this->contactPerClientLimiter->create($client), $this->contactGlobalLimiter->create()] as $limiter) {
            $limit = $limiter->consume();
            if (!$limit->isAccepted()) {
                throw new TooManyRequestsHttpException(
                    max(1, $limit->getRetryAfter()->getTimestamp() - time()),
                    'Too many messages. Please try again later.',
                );
            }
        }

        $message = $this->persistProcessor->process($data, $operation, $uriVariables, $context);

        // The message is stored either way, so a mail outage must not fail the submission.
        try {
            $this->mailer->notifyInbox($message);
            $this->mailer->sendConfirmation($message);
        } catch (TransportExceptionInterface $exception) {
            $this->logger->error('Could not send contact form emails for message {id}: {error}', [
                'id' => $message->id,
                'error' => $exception->getMessage(),
            ]);
        }

        return $message;
    }
}
