<?php

require __DIR__ . '/auth.php';
migrate_legacy_data();
$screen = (string) ($_GET['screen'] ?? '');
$file = basename((string) ($_GET['file'] ?? ''));
if (!valid_screen($screen) || !safe_event_file($file)) {
    http_response_code(400);
    exit;
}
$allowed = false;
foreach (events() as $event) {
    if (($event['display'] ?? '') === $screen && ($event['bild'] ?? '') === $file) {
        $allowed = true;
        break;
    }
}
$path = media_path($file);
if (!$allowed || !is_file($path)) {
    http_response_code(404);
    exit;
}
header('Content-Type: ' . (strtolower(pathinfo($file, PATHINFO_EXTENSION)) === 'mp4' ? 'video/mp4' : 'image/jpeg'));
header('Content-Length: ' . filesize($path));
header('Cache-Control: private, max-age=3600');
readfile($path);
