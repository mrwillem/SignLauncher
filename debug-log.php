<?php
$allowed = [
    'loadedmetadata', 'canplay', 'playing', 'ended',
    'error', 'stalled', 'waiting', 'pause',
    'play-resolved', 'play-rejected', 'watchdog'
];

$event = (string) ($_GET['event'] ?? '');

if (!in_array($event, $allowed, true)) {
    http_response_code(400);
    exit;
}

$time = date('Y-m-d H:i:s');
$screen = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['screen'] ?? '');
$current = is_numeric($_GET['current'] ?? null)
    ? (float) $_GET['current'] : 0;
$duration = is_numeric($_GET['duration'] ?? null)
    ? (float) $_GET['duration'] : 0;
$error = is_numeric($_GET['error'] ?? null)
    ? (int) $_GET['error'] : 0;

$line = sprintf(
    "%s screen=%s event=%s current=%.2f duration=%.2f error=%d\n",
    $time, $screen, $event, $current, $duration, $error
);

file_put_contents(
    __DIR__ . '/video-debug.log',
    $line,
    FILE_APPEND | LOCK_EX
);

header('Cache-Control: no-store');
header('Content-Type: text/plain');
echo 'OK';
