<?php

require __DIR__ . '/auth.php';
migrate_legacy_data();
$screen = (string) ($_GET['screen'] ?? '');
if (!valid_screen($screen)) {
    http_response_code(400);
    exit('Unbekanntes Display.');
}
$now = time();
$selected = null;
foreach (events() as $event) {
    if (($event['display'] ?? '') !== $screen) {
        continue;
    }
    $start = strtotime((string) ($event['start'] ?? ''));
    $end = strtotime((string) ($event['ende'] ?? ''));
    if ($start !== false && $end !== false && $start <= $now && $now <= $end) {
        if ($selected === null || ($event['priority'] ?? 0) > ($selected['priority'] ?? 0) || ($event['start'] ?? '') > ($selected['start'] ?? '')) {
            $selected = $event;
        }
    }
}
$file = $selected !== null ? (string) $selected['bild'] : 'standard_' . $screen . '.jpg';
$image = $selected !== null ? 'media.php?screen=' . rawurlencode($screen) . '&file=' . rawurlencode($file) : $file;
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
echo json_encode(['image' => $image, 'type' => strtolower(pathinfo($file, PATHINFO_EXTENSION)) === 'mp4' ? 'video' : 'image', 'updated_at' => gmdate('c')], JSON_UNESCAPED_SLASHES);
