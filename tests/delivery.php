<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/ReminderDelivery.php';

$assertions = 0;
function check(bool $condition, string $message): void
{
    global $assertions;
    ++$assertions;
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

final class FakeTransport implements ReminderTransport
{
    public array $calls = [];
    public function __construct(private array $results) {}
    public function send(string $recipient, string $message): bool
    {
        $this->calls[] = [$recipient, $message];
        $result = array_shift($this->results);
        if ($result instanceof Throwable) {
            throw $result;
        }
        return $result;
    }
}

foreach ([
    [[true, true, true, true, true], 'accepted', 200],
    [[false, true, true, true, true], 'partial', 502],
    [[true, true, true, true, false], 'partial', 502],
    [[false, false, true, false, false], 'partial', 502],
    [[false, false, false, false, false], 'failed', 502],
    [[new RuntimeException('private SMTP error'), true, true, true, true], 'partial', 502],
] as [$results, $status, $httpStatus]) {
    $transport = new FakeTransport($results);
    $factoryCalls = 0;
    ob_start();
    $report = ReminderDelivery::send('2025550100', "Literal <b>message</b>\nSecond line", function () use ($transport, &$factoryCalls) {
        ++$factoryCalls;
        return $transport;
    });
    check(ob_get_clean() === '', 'Delivery must not print request or SMTP diagnostics.');
    check($report['status'] === $status, 'Must aggregate every outcome, not only the final send.');
    check($report['http_status'] === $httpStatus, 'HTTP status must reflect aggregate outcome.');
    check(count($report['outcomes']) === 5 && count($transport->calls) === 5, 'Every gateway is attempted once, including after exceptions.');
    check($factoryCalls === 1, 'One transport is created for a valid request.');
    check(count(array_unique(array_column($transport->calls, 0))) === 5, 'Gateway recipients must be distinct.');
    check($transport->calls[0] === ['2025550100@messaging.sprintpcs.com', "Literal <b>message</b>\nSecond line"], 'Pass the literal message and resolved phone to the adapter.');
    check(!str_contains($report['message'], '2025550100') && !str_contains($report['message'], 'private SMTP error'), 'Public result must not leak request or transport details.');
    if ($status === 'partial') {
        check(str_contains($report['message'], 'duplicate'), 'Partial acceptance must warn against blind retries.');
    }
}

foreach (['2025550100', '1-202-555-0100', '+1 (202) 555-0100', ' (202) 555-0100 ', '202.555.0100'] as $phone) {
    $transport = new FakeTransport(array_fill(0, 5, true));
    $report = ReminderDelivery::send($phone, 'Hello', fn () => $transport);
    check($report['status'] === 'accepted', 'Accept common North American phone formatting.');
    check($transport->calls[0][0] === '2025550100@messaging.sprintpcs.com', 'Normalize phone formatting once.');
}

foreach ([null, [], 2025550100, '', '202555010', '202555010000', 'person@example.test', "2025550100\r\nBcc: x@example.test", '+44 2025550100', '2025550100@other.test', '2025550100,3035550100'] as $phone) {
    $factoryCalls = 0;
    $report = ReminderDelivery::send($phone, 'Hello', function () use (&$factoryCalls) { ++$factoryCalls; throw new RuntimeException('must not run'); });
    check($report['status'] === 'invalid' && $report['http_status'] === 422, 'Reject malformed phone values.');
    check($factoryCalls === 0 && $report['outcomes'] === [], 'Invalid phone must not initialize transport or attempt delivery.');
}
foreach ([null, [], 123, '', " \t\r\n"] as $message) {
    $factoryCalls = 0;
    $report = ReminderDelivery::send('2025550100', $message, function () use (&$factoryCalls) { ++$factoryCalls; throw new RuntimeException('must not run'); });
    check($report['status'] === 'invalid', 'Reject missing, malformed or blank messages.');
    check($factoryCalls === 0 && $report['outcomes'] === [], 'Invalid message must not initialize transport.');
}
$report = ReminderDelivery::send('2025550100', 'Hello', function () { throw new RuntimeException('private configuration error'); });
check($report['status'] === 'unavailable' && $report['http_status'] === 503 && $report['outcomes'] === [], 'Configuration failure must not claim delivery attempts.');
check(!str_contains($report['message'], 'private configuration error'), 'Configuration diagnostics must remain private.');
$report = ReminderDelivery::send('2025550100', 'Hello', fn () => new stdClass());
check($report['status'] === 'unavailable', 'Invalid transport must fail closed.');
echo "Delivery contracts passed: $assertions assertions\n";
