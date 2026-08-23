<?php
require __DIR__ . '/auth.php'; migrate_legacy_data();
$screen = (string) ($_GET['screen'] ?? ''); $token = (string) ($_GET['token'] ?? ''); $file = basename((string) ($_GET['file'] ?? ''));
if (!authorize_screen($screen, $token) || !safe_event_file($file)) { http_response_code(403); exit; }
$allowed = false;
foreach (events() as $event) if (($event['display'] ?? '') === $screen && ($event['bild'] ?? '') === $file) { $allowed = true; break; }
$path = media_path($file);
if (!$allowed || !is_file($path)) { http_response_code(404); exit; }
header('Content-Type: image/jpeg'); header('Content-Length: ' . filesize($path)); header('Cache-Control: private, max-age=3600'); readfile($path);
