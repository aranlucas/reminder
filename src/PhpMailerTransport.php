<?php

declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;

require_once __DIR__ . '/ReminderDelivery.php';

final class PhpMailerTransport implements ReminderTransport
{
    public function __construct(private PHPMailer $mailer) {}

    public function send(string $recipient, string $message): bool
    {
        $this->mailer->clearAllRecipients();
        $this->mailer->clearAttachments();
        try {
            $this->mailer->SMTPDebug = 0;
            $this->mailer->isHTML(false);
            $this->mailer->CharSet = PHPMailer::CHARSET_UTF8;
            $this->mailer->Body = $message;
            // PHP's empty() treats the non-empty text "0" as empty.
            $this->mailer->AllowEmpty = $message === '0';
            $this->mailer->AltBody = '';
            if (!$this->mailer->addAddress($recipient)) {
                return false;
            }
            return $this->mailer->send();
        } finally {
            // This also runs for false results and exceptions before the next gateway.
            $this->mailer->clearAllRecipients();
        }
    }
}
