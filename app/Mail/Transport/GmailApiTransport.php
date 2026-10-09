<?php

namespace App\Mail\Transport;

use Google\Client;
use Google\Service\Gmail;
use Google\Service\Gmail\Message as GmailMessage;
use GuzzleHttp\Client as HttpClient;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Throwable;

class GmailApiTransport extends AbstractTransport
{
    public function __construct(
        private readonly ?string $clientId,
        private readonly ?string $clientSecret,
        private readonly ?string $refreshToken,
        private readonly ?\Closure $apiSender = null,
    ) {
        parent::__construct();
    }

    protected function doSend(SentMessage $message): void
    {
        try {
            $rawMessage = $this->encodeMessage($message->toString());
            $messageId = $this->apiSender
                ? ($this->apiSender)($rawMessage)
                : $this->sendWithGmailApi($rawMessage);

            if (is_string($messageId) && $messageId !== '') {
                $message->setMessageId($messageId);
            }
        } catch (TransportException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new TransportException(
                'Gmail API could not send the email: '.$exception->getMessage(),
                0,
                $exception,
            );
        }
    }

    private function sendWithGmailApi(string $rawMessage): string
    {
        if (blank($this->clientId) || blank($this->clientSecret) || blank($this->refreshToken)) {
            throw new TransportException('Gmail API OAuth credentials are not configured.');
        }

        $client = new Client();
        $client->setClientId($this->clientId);
        $client->setClientSecret($this->clientSecret);
        $client->setScopes(['https://www.googleapis.com/auth/gmail.send']);
        $client->setAccessType('offline');
        $client->setHttpClient(new HttpClient([
            'connect_timeout' => 5,
            'timeout' => 10,
        ]));

        $token = $client->fetchAccessTokenWithRefreshToken($this->refreshToken);
        if (! empty($token['error']) || empty($token['access_token'])) {
            $reason = $token['error_description'] ?? $token['error'] ?? 'No access token returned.';
            throw new TransportException('Gmail OAuth token refresh failed: '.$reason);
        }

        $client->setAccessToken($token);
        $gmail = new Gmail($client);
        $gmailMessage = new GmailMessage();
        $gmailMessage->setRaw($rawMessage);

        $result = $gmail->users_messages->send('me', $gmailMessage);

        return (string) $result->getId();
    }

    private function encodeMessage(string $message): string
    {
        return rtrim(strtr(base64_encode($message), '+/', '-_'), '=');
    }

    public function __toString(): string
    {
        return 'gmail-api';
    }
}