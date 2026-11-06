<?php

declare(strict_types=1);

require_once __DIR__ . '/src/PhpMailerTransport.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Allow: POST');
    $report = ['http_status' => 405, 'message' => 'Submit the message form to send a reminder.'];
} else {
    $report = ReminderDelivery::send(
        $_POST['email'] ?? null,
        $_POST['message'] ?? null,
        [PhpMailerTransport::class, 'fromEnvironment']
    );
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
