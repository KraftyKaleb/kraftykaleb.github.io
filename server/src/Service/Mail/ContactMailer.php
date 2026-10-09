<?php
declare(strict_types=1);

namespace App\Service\Mail;

use App\Model\Entity\ContactMessage;
use App\Model\Entity\ContactReply;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

/**
 * Sends the emails around the contact form: a confirmation to the sender, a notification
 * to the site owner, and admin replies.
 */
final readonly class ContactMailer {
    public function __construct(
        private MailerInterface $mailer,
        #[Autowire(env: 'MAILER_FROM')]
        private string $from,
        #[Autowire(env: 'CONTACT_INBOX')]
        private string $inbox,
    ) {
    }

    /** @throws TransportExceptionInterface */
    public function sendConfirmation(ContactMessage $message): void {
        $this->mailer->send(new TemplatedEmail()
            ->from($this->from)
            ->to(new Address($message->email, $message->name))
            ->replyTo($this->inbox)
            ->subject('Thanks for getting in touch')
            ->textTemplate('email/contact/confirmation.txt.twig')
            ->context(['contact' => $message]));
    }

    /** @throws TransportExceptionInterface */
    public function notifyInbox(ContactMessage $message): void {
        $this->mailer->send(new TemplatedEmail()
            ->from($this->from)
            ->to($this->inbox)
            ->replyTo(new Address($message->email, $message->name))
            ->subject(sprintf('New contact message from %s', $message->name))
            ->textTemplate('email/contact/notification.txt.twig')
            ->context(['contact' => $message]));
    }

    /** @throws TransportExceptionInterface */
    public function sendReply(ContactReply $reply): void {
        $message = $reply->contactMessage;
        $subject = $message->subject === '' ? 'Your message' : $message->subject;

        $this->mailer->send(new TemplatedEmail()
            ->from($this->from)
            ->to(new Address($message->email, $message->name))
            ->replyTo($this->inbox)
            ->subject('Re: '.$subject)
            ->textTemplate('email/contact/reply.txt.twig')
            ->context(['contact' => $message, 'reply' => $reply]));
    }
}
