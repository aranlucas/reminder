<?php

declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;

require_once __DIR__ . '/ReminderDelivery.php';

final class PhpMailerTransport implements ReminderTransport
{
    public function __construct(private PHPMailer $mailer) {}

    public static function fromEnvironment(): self
    {
        $username = getenv('REMINDER_SMTP_USERNAME');
        $password = getenv('REMINDER_SMTP_PASSWORD');
        $from = getenv('REMINDER_FROM_ADDRESS');
        $autoload = __DIR__ . '/../vendor/autoload.php';
        if ($username === false || $username === '' || $password === false || $password === ''
            || $from === false || !filter_var($from, FILTER_VALIDATE_EMAIL)
            || !is_file($autoload)) {
            throw new RuntimeException('SMTP configuration or dependencies are unavailable.');
        }

        require_once $autoload;
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->SMTPAuth = true;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Host = 'smtp.gmail.com';
        $mail->Port = 465;
        $mail->Timeout = 10;
        $mail->Username = $username;
        $mail->Password = $password;
        $mail->setFrom($from, 'Reminder');
        return new self($mail);
    }

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
            $this->mailer->clearAllRecipients();
        }
    }
}
