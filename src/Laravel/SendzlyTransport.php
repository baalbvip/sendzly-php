<?php

declare(strict_types=1);

namespace Sendzly\Laravel;

use Sendzly\SendzlyClient;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\MessageConverter;

/**
 * Driver oficial de Symfony Mailer / Laravel 10, 11 y 12 para Sendzly.
 * Permite usar `Mail::to(...)->send(...)` con `MAIL_MAILER=sendzly`.
 */
class SendzlyTransport extends AbstractTransport
{
    private SendzlyClient $client;

    public function __construct(SendzlyClient $client)
    {
        parent::__construct();
        $this->client = $client;
    }

    protected function doSend(SentMessage $message): void
    {
        $email = MessageConverter::toEmail($message->getOriginalMessage());
        $recipients = $email->getTo();

        if (empty($recipients)) {
            return;
        }

        $fromList = $email->getFrom();
        $fromName = !empty($fromList) ? $fromList[0]->getName() : null;
        $replyToList = $email->getReplyTo();
        $replyTo = !empty($replyToList) ? $replyToList[0]->getAddress() : null;

        $subject = (string) ($email->getSubject() ?? '');
        $html = $email->getHtmlBody();
        $text = $email->getTextBody();

        // El motor Transaccional MT de Sendzly despacha 1-a-1 para máxima entregabilidad
        foreach ($recipients as $recipient) {
            $payload = [
                'to' => $recipient->getAddress(),
                'subject' => $subject,
            ];

            if (is_string($html) && trim($html) !== '') {
                $payload['html'] = $html;
            }
            if (is_string($text) && trim($text) !== '') {
                $payload['text'] = $text;
            }
            if (is_string($fromName) && trim($fromName) !== '') {
                $payload['fromName'] = $fromName;
            }
            if (is_string($replyTo) && trim($replyTo) !== '') {
                $payload['replyTo'] = $replyTo;
            }

            $this->client->send($payload);
        }
    }

    public function __toString(): string
    {
        return 'sendzly';
    }
}
