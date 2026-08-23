<?php
require __DIR__ . '/auth.php';
migrate_legacy_data(); require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }
require_csrf();
function redirect_admin(string $message): never { header('Location: formular.php?message=' . rawurlencode($message)); exit; }
if (($_POST['action'] ?? '') === 'delete') {
    $file = basename((string) ($_POST['loesch_bild'] ?? ''));
    if (!safe_event_file($file)) { http_response_code(400); exit('Ungültige Datei.'); }
    with_data_lock(function () use ($file): void { $remaining = []; foreach (events() as $event) { if (($event['bild'] ?? '') === $file) { $path = media_path($file); if (is_file($path)) unlink($path); } else $remaining[] = $event; } save_events($remaining); });
    redirect_admin('Event gelöscht.');
}
if (!isset($_FILES['menue_bild']) || !is_array($_FILES['menue_bild'])) { http_response_code(400); exit('Kein Bild hochgeladen.'); }
$upload = $_FILES['menue_bild']; $display = (string) ($_POST['display_typ'] ?? ''); $startInput = (string) ($_POST['start_zeit'] ?? ''); $endInput = (string) ($_POST['ende_zeit'] ?? '');
if (!valid_screen($display)) { http_response_code(400); exit('Unbekanntes Display.'); }
if (($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file((string) $upload['tmp_name'])) { http_response_code(400); exit('Upload fehlgeschlagen.'); }
if (($upload['size'] ?? 0) > 5 * 1024 * 1024) { http_response_code(400); exit('Das Bild ist größer als 5 MB.'); }
$start = DateTimeImmutable::createFromFormat('Y-m-d\\TH:i', $startInput); $end = DateTimeImmutable::createFromFormat('Y-m-d\\TH:i', $endInput);
if (!$start || !$end || $start >= $end) { http_response_code(400); exit('Der Zeitraum ist ungültig.'); }
$info = @getimagesize((string) $upload['tmp_name']);
if ($info === false || ($info[2] ?? 0) !== IMAGETYPE_JPEG || $info[0] > 8000 || $info[1] > 8000) { http_response_code(400); exit('Nur JPEG-Bilder bis 8000 × 8000 Pixel sind erlaubt.'); }
$originalName = basename((string) $upload['name']); $file = 'display_' . $display . '_' . bin2hex(random_bytes(12)) . '.jpg';
ensure_data_dir(); if (!is_dir(app_path('media'))) mkdir(app_path('media'), 0700, true);
if (!move_uploaded_file((string) $upload['tmp_name'], media_path($file))) { http_response_code(500); exit('Bild konnte nicht gespeichert werden.'); }
@chmod(media_path($file), 0600);
try { with_data_lock(function () use ($display, $start, $end, $originalName, $file): void { $kept = []; $threshold = time() - 86400; foreach (events() as $event) { if (strtotime((string) ($event['ende'] ?? '')) < $threshold) { $old = (string) ($event['bild'] ?? ''); if (safe_event_file($old) && is_file(media_path($old))) unlink(media_path($old)); } else $kept[] = $event; } $kept[] = ['original_name' => $originalName, 'display' => $display, 'start' => $start->format('Y-m-d H:i:s'), 'ende' => $end->format('Y-m-d H:i:s'), 'bild' => $file]; save_events($kept); }); } catch (Throwable $e) { @unlink(media_path($file)); throw $e; }
redirect_admin('Event gespeichert.');
