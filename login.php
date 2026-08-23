<?php
require __DIR__ . '/auth.php'; migrate_legacy_data();
if (!is_configured()) { header('Location: setup.php'); exit; }
if (logged_in()) { header('Location: formular.php'); exit; }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $password = (string) ($_POST['password'] ?? '');
    $allowed = with_data_lock(function () use ($password): bool {
        $data = auth_data();
        if (login_rate_limited($data)) return false;
        $hash = $data['password_hash'] ?? '';
        if (is_string($hash) && password_verify($password, $hash)) { clear_failed_logins($data); save_auth_data($data); return true; }
        record_failed_login($data); save_auth_data($data); return false;
    });
    if ($allowed) { login_user(); header('Location: formular.php'); exit; }
    $error = 'Anmeldung fehlgeschlagen oder vorübergehend gesperrt.';
}
?><!doctype html><html lang="de"><meta charset="utf-8"><title>Signage anmelden</title><body><h1>Signage-Verwaltung</h1><?php if ($error): ?><p><?= h($error) ?></p><?php endif; ?><form method="post"><input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>"><label>Passwort <input type="password" name="password" autocomplete="current-password" required></label><button>Anmelden</button></form><p><a href="reset_password.php">Passwort mit Wiederherstellungscode zurücksetzen</a></p></body></html>
