<?php

namespace App\MessageHandler;

use App\Message\CommentMessage;
use App\Repository\CommentRepository;
use App\Service\SpamChecker;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\NotificationEmail;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Workflow\WorkflowInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

#[AsMessageHandler]
class CommentMessageHandler
{
    public function __construct(
        private readonly EntityManagerInterface              $entityManager,
        private readonly SpamChecker                         $spamChecker,
        private readonly CommentRepository                   $commentRepository,
        private readonly MessageBusInterface                 $bus,
        private readonly WorkflowInterface                   $commentStateMachine,
        private readonly LoggerInterface                     $logger,
        private readonly MailerInterface                     $mailer,
        #[Autowire('%admin_email%')] private readonly string $adminEmail,
    ) {
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface|\Symfony\Component\Mailer\Exception\TransportExceptionInterface
     */
    public function __invoke(CommentMessage $message): void
    {
        $comment = $this->commentRepository->find($message->getId());
        if (!$comment) {
            return;
        }

        if ($this->commentStateMachine->can($comment, 'accept')) {
            $score = $this->spamChecker->getSpamScore($comment, $message->getContext());
            $transition = match ($score) {
                SpamChecker::BLATANT_SPAM => 'reject_spam',
                SpamChecker::MAYBE_SPAM   => 'might_be_spam',
                default                   => 'accept',
            };
            $this->commentStateMachine->apply($comment, $transition);
            $this->entityManager->flush();
            $this->bus->dispatch($message);
        } else if ($this->commentStateMachine->can($comment, 'publish') || $this->commentStateMachine->can($comment, 'publish_ham')) {
//            $this->commentStateMachine->apply($comment, $this->commentStateMachine->can($comment, 'publish') ? 'publish' : 'publish_ham');
//            $this->entityManager->flush();
            $this->mailer->send((New NotificationEmail())
                ->subject('New comment has been posted')
                ->htmlTemplate('emails/comment_notification.html.twig')
                ->to($this->adminEmail)
                ->context(['comment' => $comment])
            );
        } else {
            $this->logger->debug('Dropping comment message', [
                'comment' => $comment->getId(),
                'state'   => $comment->getState(),
            ]);
        }

        $this->entityManager->flush();
    }
}
