<?php

namespace App\Mail\Transport;

use Illuminate\Support\Facades\Http;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\MessageConverter;
use Throwable;

class ResendApiTransport extends AbstractTransport
{
    public function __construct(private readonly ?string $apiKey)
    {
        parent::__construct();
    }

    protected function doSend(SentMessage $message): void
    {
        if (blank($this->apiKey)) {
            throw new TransportException('Resend API key is not configured.');
        }

        $email = MessageConverter::toEmail($message->getOriginalMessage());
        $sender = $message->getEnvelope()->getSender();
        $payload = array_filter([
            'from' => $sender->toString(),
            'to' => $this->stringifyAddresses($email->getTo()),
            'cc' => $this->stringifyAddresses($email->getCc()),
            'bcc' => $this->stringifyAddresses($email->getBcc()),
            'reply_to' => $this->stringifyAddresses($email->getReplyTo()),
            'subject' => $email->getSubject(),
            'html' => $email->getHtmlBody(),
            'text' => $email->getTextBody(),
        ], static fn (mixed $value): bool => $value !== null && $value !== [] && $value !== '');

        try {
            $response = Http::withToken($this->apiKey)
                ->acceptJson()
                ->timeout(10)
                ->post('https://api.resend.com/emails', $payload)
                ->throw();
        } catch (Throwable $exception) {
            throw new TransportException(
                'Resend API could not send the email: '.$exception->getMessage(),
                0,
                $exception,
            );
        }

        $messageId = $response->json('id');
        if (! is_string($messageId) || $messageId === '') {
            throw new TransportException('Resend API returned no message ID.');
        }

        $message->setMessageId($messageId);
    }

    public function __toString(): string
    {
        return 'resend-api';
    }
}