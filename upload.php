<?php

require __DIR__ . '/auth.php';
migrate_legacy_data();
require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}
require_csrf();
if (($_POST['action'] ?? '') === 'save_default') {
    $display = (string) ($_POST['display_typ'] ?? '');

    if (!valid_screen($display)) {
        http_response_code(400);
        exit('Unbekanntes Display.');
    }

    if (
        !isset($_FILES['default_content']) ||
        !is_array($_FILES['default_content'])
    ) {
        http_response_code(400);
        exit('Kein Default Content hochgeladen.');
    }

    $upload = $_FILES['default_content'];

    if (
        ($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK ||
        !is_uploaded_file((string) $upload['tmp_name'])
    ) {
        http_response_code(400);
        exit('Upload fehlgeschlagen.');
    }

    if (($upload['size'] ?? 0) > 25 * 1024 * 1024) {
        http_response_code(400);
        exit('Die Datei ist größer als 25 MB.');
    }

    $extension = strtolower(
        pathinfo((string) $upload['name'], PATHINFO_EXTENSION)
    );

    if ($extension === 'jpeg') {
        $extension = 'jpg';
    }

    if (!in_array($extension, ['jpg', 'mp4'], true)) {
        http_response_code(400);
        exit('Nur JPG und MP4 sind erlaubt.');
    }

    if ($extension === 'jpg') {
        $info = @getimagesize((string) $upload['tmp_name']);

        if (
            $info === false ||
            ($info[2] ?? 0) !== IMAGETYPE_JPEG ||
            $info[0] > 8000 ||
            $info[1] > 8000
        ) {
            http_response_code(400);
            exit(
                'JPEG-Bilder dürfen höchstens 8000 × 8000 Pixel groß sein.'
            );
        }
    }

    if (
        $extension === 'mp4' &&
        (new finfo(FILEINFO_MIME_TYPE))->file(
            (string) $upload['tmp_name']
        ) !== 'video/mp4'
    ) {
        http_response_code(400);
        exit('Ungültige MP4-Datei.');
    }

    ensure_data_dir();
    with_data_lock(function () use ($display, $upload, $extension): void {
        ensure_data_dir();

        if (!is_dir(app_path('media'))) {
            mkdir(app_path('media'), 0700, true);
        }

        $file = 'standard_' . $display . '.' . $extension;
        $path = media_path($file);

        foreach (['jpg', 'mp4'] as $oldExtension) {
            $oldFile = 'standard_' . $display . '.' . $oldExtension;

            if ($oldFile !== $file && is_file(media_path($oldFile))) {
                unlink(media_path($oldFile));
            }
        }

        if (!move_uploaded_file((string)$upload['tmp_name'], $path)) {
            throw new RuntimeException(
                'Default Content konnte nicht gespeichert werden.'
            );
        }


        @chmod($path, 0600);
    });

    header('Location: screens.php?message=' . rawurlencode(
        'Default Content gespeichert.'
    ));
    exit;
}
function redirect_admin(string $message): never
{
    header('Location: formular.php?message=' . rawurlencode($message));
    exit;
}
if (($_POST['action'] ?? '') === 'delete_default') {
    $display = (string) ($_POST['display_typ'] ?? '');

    if (!valid_screen($display)) {
        http_response_code(400);
        exit('Unbekanntes Display.');
    }

    with_data_lock(function () use ($display): void {
        foreach (['jpg', 'mp4'] as $extension) {
            $file = 'standard_' . $display . '.' . $extension;
            $path = media_path($file);

            if (is_file($path)) {
                unlink($path);
            }
        }
    });

    header('Location: screens.php?message=' . rawurlencode(
            'Default Content gelöscht.'
        ));
    exit;
}
if (($_POST['action'] ?? '') === 'delete') {
    $file = basename((string) ($_POST['loesch_bild'] ?? ''));
    if (!safe_event_file($file)) {
        http_response_code(400);
        exit('Ungültige Datei.');
    }
    with_data_lock(function () use ($file): void {
        $remaining = [];
        foreach (events() as $event) {
            if (($event['bild'] ?? '') === $file) {
                $path = media_path($file);
                if (is_file($path)) {
                    unlink($path);
                }
            } else {
                $remaining[] = $event;
            }
        } save_events($remaining);
    });
    redirect_admin('Event gelöscht.');
}
if (!isset($_FILES['menue_bild']) || !is_array($_FILES['menue_bild'])) {
    http_response_code(400);
    exit('Kein Bild hochgeladen.');
}
$upload = $_FILES['menue_bild'];
$display = (string) ($_POST['display_typ'] ?? '');
$startInput = (string) ($_POST['start_zeit'] ?? '');
$endInput = (string) ($_POST['ende_zeit'] ?? '');
if (!valid_screen($display)) {
    http_response_code(400);
    exit('Unbekanntes Display.');
}
if (($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file((string) $upload['tmp_name'])) {
    http_response_code(400);
    exit('Upload fehlgeschlagen.');
}
if (($upload['size'] ?? 0) > 25 * 1024 * 1024) {
    http_response_code(400);
    exit('Die Datei ist größer als 25 MB.');
}
$start = DateTimeImmutable::createFromFormat('Y-m-d\\TH:i', $startInput);
$end = DateTimeImmutable::createFromFormat('Y-m-d\\TH:i', $endInput);
if (!$start || !$end || $start >= $end) {
    http_response_code(400);
    exit('Der Zeitraum ist ungültig.');
}
$extension = strtolower(pathinfo((string) $upload['name'], PATHINFO_EXTENSION));
$info = $extension === 'jpg' || $extension === 'jpeg' ? @getimagesize((string) $upload['tmp_name']) : false;
if (($extension === 'jpg' || $extension === 'jpeg') && ($info === false || ($info[2] ?? 0) !== IMAGETYPE_JPEG || $info[0] > 8000 || $info[1] > 8000)) {
    http_response_code(400);
    exit('JPEG-Bilder dürfen höchstens 8000 × 8000 Pixel groß sein.');
}
if ($extension === 'mp4' && (new finfo(FILEINFO_MIME_TYPE))->file((string) $upload['tmp_name']) !== 'video/mp4') {
    http_response_code(400);
    exit('Ungültige MP4-Datei.');
}
if (!in_array($extension, ['jpg', 'jpeg', 'mp4'], true)) {
    http_response_code(400);
    exit('Nur JPG und MP4 sind erlaubt.');
}
$originalName = basename((string) $upload['name']);
$file = 'display_' . $display . '_' . bin2hex(random_bytes(12)) . '.' . ($extension === 'jpeg' ? 'jpg' : $extension);
ensure_data_dir();
if (!is_dir(app_path('media'))) {
    mkdir(app_path('media'), 0700, true);
}
if (!move_uploaded_file((string) $upload['tmp_name'], media_path($file))) {
    http_response_code(500);
    exit('Bild konnte nicht gespeichert werden.');
}
@chmod(media_path($file), 0600);
try {
    with_data_lock(function () use ($display, $start, $end, $originalName, $file): void {
        $kept = [];
        $threshold = time() - 86400;
        foreach (events() as $event) {
            if (strtotime((string) ($event['ende'] ?? '')) < $threshold) {
                $old = (string) ($event['bild'] ?? '');
                if (safe_event_file($old) && is_file(media_path($old))) {
                    unlink(media_path($old));
                }
            } else {
                $kept[] = $event;
            }
        } $kept[] = ['original_name' => $originalName, 'display' => $display, 'start' => $start->format('Y-m-d H:i:s'), 'ende' => $end->format('Y-m-d H:i:s'), 'bild' => $file];
        save_events($kept);
    });
} catch (Throwable $e) {
    @unlink(media_path($file));
    throw $e;
}
redirect_admin('Event gespeichert.');
