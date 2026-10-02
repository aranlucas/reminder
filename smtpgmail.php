<?php

declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;

require_once __DIR__ . '/src/ReminderDelivery.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Allow: POST');
    $report = ['http_status' => 405, 'message' => 'Submit the message form to send a reminder.'];
} else {
    $report = ReminderDelivery::send($_POST['email'] ?? null, $_POST['message'] ?? null, function (): ReminderTransport {
        $username = getenv('REMINDER_SMTP_USERNAME');
        $password = getenv('REMINDER_SMTP_PASSWORD');
        $from = getenv('REMINDER_FROM_ADDRESS');
        if ($username === false || $username === '' || $password === false || $password === ''
            || $from === false || !filter_var($from, FILTER_VALIDATE_EMAIL)
            || !is_file(__DIR__ . '/vendor/autoload.php')) {
            throw new RuntimeException('SMTP configuration or dependencies are unavailable.');
        }

        require_once __DIR__ . '/vendor/autoload.php';
        require_once __DIR__ . '/src/PhpMailerTransport.php';
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->SMTPDebug = 0;
        $mail->SMTPAuth = true;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Host = 'smtp.gmail.com';
        $mail->Port = 465;
        $mail->Timeout = 10;
        $mail->Username = $username;
        $mail->Password = $password;
        $mail->setFrom($from, 'Reminder');
        return new PhpMailerTransport($mail);
    });
}

http_response_code($report['http_status']);
header('Content-Type: text/html; charset=UTF-8');
header('Cache-Control: no-store');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Message result · Reminder</title>
  <link href="css/bootstrap.min.css" rel="stylesheet">
  <link href="css/reminder.css" rel="stylesheet">
</head>
<body>
  <main class="page-width">
    <h1>Message result</h1>
    <p><?= htmlspecialchars($report['message'], ENT_QUOTES, 'UTF-8') ?></p>
    <p><a href="index.html">Back to the message form</a></p>
  </main>
</body>
</html>
