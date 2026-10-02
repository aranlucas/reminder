<?php

declare(strict_types=1);

// Exercise the actual entrypoint in subprocesses; remove mail configuration so it
// cannot initialize SMTP, regardless of the machine's environment.
$cases = [
    ['GET', [], 405, 'Submit the message form'],
    ['POST', [], 422, 'Enter a 10-digit phone'],
    ['POST', ['email' => [], 'message' => 'fixture'], 422, 'Enter a 10-digit phone'],
    ['POST', ['email' => '2025550100', 'message' => []], 422, 'Enter a message'],
    ['POST', ['email' => '2025550100', 'message' => '<script>private fixture</script>'], 503, 'Messaging is unavailable'],
];
foreach ($cases as [$method, $post, $status, $message]) {
    $script = 'putenv("REMINDER_SMTP_USERNAME"); putenv("REMINDER_SMTP_PASSWORD"); putenv("REMINDER_FROM_ADDRESS");'
        . '$_SERVER["REQUEST_METHOD"] = ' . var_export($method, true) . ';'
        . '$_POST = ' . var_export($post, true) . ';'
        . 'ob_start(); require ' . var_export(__DIR__ . '/../smtpgmail.php', true) . '; $html = ob_get_clean();'
        . 'echo json_encode(["status" => http_response_code(), "html" => $html]);';
    $process = proc_open([PHP_BINARY, '-r', $script], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    if (!is_resource($process)) {
        throw new RuntimeException('Cannot run HTTP entrypoint fixture.');
    }
    $output = stream_get_contents($pipes[1]);
    $error = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $exit = proc_close($process);
    $result = json_decode($output, true);
    if ($exit !== 0 || $error !== '' || !is_array($result) || $result['status'] !== $status || !str_contains($result['html'], $message)) {
        throw new RuntimeException('Incorrect entrypoint response: ' . $output . $error);
    }
    if (str_contains($result['html'], '2025550100') || str_contains($result['html'], 'private fixture') || str_contains($result['html'], 'SMTP Error')) {
        throw new RuntimeException('Response contains private request or SMTP details.');
    }
}
echo "HTTP entrypoint contracts passed: " . count($cases) . " cases\n";
