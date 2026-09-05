<?php
require __DIR__ . '/auth.php';
migrate_legacy_data();
if (!is_configured()) {
    header('Location: setup.php');
    exit;
} $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $code = (string) ($_POST['recovery_code'] ?? '');
    $new = (string) ($_POST['new_password'] ?? '');
    $confirm = (string) ($_POST['confirm_password'] ?? '');
    $data = auth_data();
    if (!hash_equals((string) ($data['recovery_hash'] ?? ''), recovery_code_hash($code))) {
        $error = 'Der Wiederherstellungscode ist ungültig.';
    } elseif (!valid_password($new)) {
        $error = 'Das Passwort muss mindestens 12 Zeichen lang sein.';
    } elseif (!hash_equals($new, $confirm)) {
        $error = 'Die Passwörter stimmen nicht überein.';
    } else {
        $data['password_hash'] = password_hash($new, PASSWORD_ARGON2ID);
        save_auth_data($data);
        login_user();
        header('Location: formular.php');
        exit;
    }
}
?><!doctype html><html lang="de"><meta charset="utf-8"><title>Passwort zurücksetzen</title><body><h1>Passwort zurücksetzen</h1><?php if ($error): ?><p><?= h($error) ?></p><?php endif; ?><form method="post"><input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>"><label>Wiederherstellungscode <input type="password" name="recovery_code" required></label><br><label>Neues Passwort <input type="password" name="new_password" minlength="12" required></label><br><label>Wiederholen <input type="password" name="confirm_password" minlength="12" required></label><br><button>Passwort zurücksetzen</button></form><p><a href="login.php">Zur Anmeldung</a></p></body></html>
