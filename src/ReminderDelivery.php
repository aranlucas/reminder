<?php

declare(strict_types=1);

interface ReminderTransport
{
    /** True means the gateway accepted the message, not that the phone received it. */
    public function send(string $recipient, string $message): bool;
}

final class ReminderDelivery
{
    // Preserve the application's existing gateway list; availability is provider-dependent.
    private const GATEWAYS = [
        'messaging.sprintpcs.com',
        'vtext.com',
        'tmomail.net',
        'txt.att.net',
        'mymetropcs.com',
    ];

    /**
     * The transport is created only after validation. Each gateway is attempted once.
     * Outcomes are for internal use; only the constant public message belongs in HTML.
     *
     * @param callable(): ReminderTransport $makeTransport
     * @return array{status: string, http_status: int, message: string, outcomes: array<string, bool>}
     */
    public static function send(mixed $phone, mixed $message, callable $makeTransport): array
    {
        if (!is_string($phone) || !preg_match(
            '/\A(?:\+?1[ .-]?)?(?:\([0-9]{3}\)|[0-9]{3})[ .-]?[0-9]{3}[ .-]?[0-9]{4}\z/',
            trim($phone)
        )) {
            return self::report('invalid', 422, 'Enter a 10-digit phone number, optionally with the +1 country code.');
        }
        if (!is_string($message) || trim($message) === '') {
            return self::report('invalid', 422, 'Enter a message before sending.');
        }

        $number = preg_replace('/[^0-9]/', '', $phone);
        if (strlen($number) === 11) {
            $number = substr($number, 1);
        }

        try {
            $transport = $makeTransport();
            if (!$transport instanceof ReminderTransport) {
                throw new RuntimeException('Invalid reminder transport.');
            }
        } catch (Throwable $error) {
            return self::report('unavailable', 503, 'Messaging is unavailable. Check the server mail configuration.');
        }

        $outcomes = [];
        foreach (self::GATEWAYS as $gateway) {
            $recipient = $number . '@' . $gateway;
            try {
                $outcomes[$recipient] = $transport->send($recipient, $message);
            } catch (Throwable $error) {
                $outcomes[$recipient] = false;
            }
        }

        $accepted = count(array_filter($outcomes));
        if ($accepted === count($outcomes)) {
            return self::report('accepted', 200, 'All configured gateways accepted the message. Phone delivery is not confirmed.', $outcomes);
        }
        if ($accepted > 0) {
            return self::report('partial', 502, 'Some gateways accepted the message, but others failed. Phone delivery is not confirmed. Retrying may send duplicate messages.', $outcomes);
        }
        return self::report('failed', 502, 'No gateway accepted the message. Please try again later.', $outcomes);
    }

    private static function report(string $status, int $httpStatus, string $message, array $outcomes = []): array
    {
        return ['status' => $status, 'http_status' => $httpStatus, 'message' => $message, 'outcomes' => $outcomes];
    }
}
