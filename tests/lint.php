<?php

declare(strict_types=1);

$files = array_merge([__DIR__ . '/../smtpgmail.php'], glob(__DIR__ . '/../src/*.php'), glob(__DIR__ . '/*.php'));
foreach ($files as $file) {
    passthru(escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($file), $status);
    if ($status !== 0) {
        exit($status);
    }
}
