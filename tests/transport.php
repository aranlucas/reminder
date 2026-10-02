<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/PhpMailerTransport.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception as MailerException;

// The real PHPMailer builds messages and drives its SMTP protocol. This fake SMTP
// adapter records every operation without creating a socket or using credentials.
final class FakeSmtp extends SMTP
{
    public array $deliveries = [];
    public array $recipients = [];
    public array $results = [false, true, true];
    public bool $throwOnData = false;
    public bool $rejectRecipient = false;
    public function connected() { return true; }
    public function mail($from) { $this->recipients = []; return true; }
    public function recipient($address, $dsn = '')
    {
        $this->recipients[] = $address;
        return !$this->rejectRecipient;
    }
    public function data($message)
    {
        $this->deliveries[] = ['recipients' => $this->recipients, 'message' => $message];
        if ($this->throwOnData) {
            throw new RuntimeException('private SMTP diagnostics');
        }
        return array_shift($this->results) ?? true;
    }
    public function reset() { $this->recipients = []; return true; }
    public function quit($close_on_error = true) { return true; }
    public function close() { $this->recipients = []; }
}

$assertions = 0;
function check(bool $condition, string $message): void
{
    global $assertions;
    ++$assertions;
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$smtp = new FakeSmtp();
$mail = new PHPMailer(true);
$mail->isSMTP();
$mail->setSMTPInstance($smtp);
$mail->setFrom('sender@example.test');
$mail->addAddress('stale@example.test');
$mail->addCC('stale-cc@example.test');
$mail->addBCC('stale-bcc@example.test');
$mail->addStringAttachment('old attachment', 'old.txt');
$mail->AltBody = 'old alternative';
$mail->SMTPDebug = 2;
$transport = new PhpMailerTransport($mail);

ob_start();
try {
    $transport->send('2025550100@vtext.com', '<img src="fixture.txt">Hello');
    throw new RuntimeException('Expected fake SMTP rejection.');
} catch (MailerException $error) {
    // A failed SMTP result must still clear recipients.
}
check($mail->getAllRecipientAddresses() === [], 'Recipients clear after SMTP rejection.');
check($mail->getAttachments() === [], 'Literal text must not embed local files or stale attachments.');
check($mail->ContentType === 'text/plain' && $mail->AltBody === '', 'Message is plain UTF-8 text without stale HTML alternatives.');
check($mail->CharSet === PHPMailer::CHARSET_UTF8 && $mail->SMTPDebug === 0, 'Set UTF-8 and disable SMTP diagnostics.');
check($smtp->deliveries[0]['recipients'] === ['2025550100@vtext.com'], 'Never retain To, CC or BCC from previous sends.');
check(str_contains($smtp->deliveries[0]['message'], '<img src="fixture.txt">Hello'), 'User markup is preserved as literal text.');
check(!str_contains($smtp->deliveries[0]['message'], 'old.txt'), 'No stale attachment is sent.');

check($transport->send('2025550100@txt.att.net', 'Second message'), 'A later gateway can succeed after the first failed.');
check($smtp->deliveries[1]['recipients'] === ['2025550100@txt.att.net'], 'Only the current recipient reaches SMTP.');
check(!str_contains($smtp->deliveries[1]['message'], 'fixture.txt') && str_contains($smtp->deliveries[1]['message'], 'Second message'), 'Each send gets only its own message body.');
check($mail->getAllRecipientAddresses() === [], 'Recipients clear after success.');

$smtp->throwOnData = true;
try {
    $transport->send('2025550100@tmomail.net', 'Third message');
    throw new LogicException('Expected fake SMTP exception.');
} catch (RuntimeException $error) {
    check($error->getMessage() === 'private SMTP diagnostics', 'Exercise transport exception path.');
}
check($mail->getAllRecipientAddresses() === [], 'Recipients clear after unexpected exceptions.');
$smtp->throwOnData = false;
$smtp->reset();
$smtp->rejectRecipient = true;
try {
    $transport->send('2025550100@vtext.com', 'Fourth message');
    throw new RuntimeException('Expected recipient rejection.');
} catch (MailerException $error) {
}
check($mail->getAllRecipientAddresses() === [], 'Recipients clear after rejection before DATA.');
check(count($smtp->deliveries) === 3, 'Rejected recipients must not reach DATA.');
check(ob_get_clean() === '', 'Transport must not emit SMTP or recipient diagnostics.');

$pipelineSmtp = new FakeSmtp();
$pipelineSmtp->results = [false, true, true, true, true];
$pipelineMail = new PHPMailer(true);
$pipelineMail->isSMTP();
$pipelineMail->setSMTPInstance($pipelineSmtp);
$pipelineMail->setFrom('sender@example.test');
$report = ReminderDelivery::send('2025550100', '0', fn () => new PhpMailerTransport($pipelineMail));
check($report['status'] === 'partial', 'Whole delivery command preserves the first failure through the real mailer.');
check(count($pipelineSmtp->deliveries) === 5, 'A non-empty zero message reaches every fake gateway.');
foreach ($pipelineSmtp->deliveries as $delivery) {
    check(count($delivery['recipients']) === 1, 'Whole command sends isolated envelopes.');
}
check($pipelineMail->getAllRecipientAddresses() === [], 'Whole command leaves no stale recipients.');
echo "PHPMailer adapter contracts passed: $assertions assertions\n";
