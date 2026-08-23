<?php
require __DIR__ . '/auth.php'; require_login(); $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf(); $current = (string) ($_POST['current_password'] ?? ''); $new = (string) ($_POST['new_password'] ?? ''); $confirm = (string) ($_POST['confirm_password'] ?? ''); $data = auth_data();
    if (!password_verify($current, (string) ($data['password_hash'] ?? ''))) $error = 'Das aktuelle Passwort ist falsch.';
    elseif (!valid_password($new)) $error = 'Das neue Passwort muss mindestens 12 Zeichen lang sein.';
    elseif (!hash_equals($new, $confirm)) $error = 'Die Passwörter stimmen nicht überein.';
    else { $data['password_hash'] = password_hash($new, PASSWORD_ARGON2ID); save_auth_data($data); header('Location: formular.php?password=changed'); exit; }
}
?><!doctype html><html lang="de"><meta charset="utf-8"><title>Passwort ändern</title><body><h1>Passwort ändern</h1><?php if ($error): ?><p><?= h($error) ?></p><?php endif; ?><form method="post"><input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>"><label>Aktuelles Passwort <input type="password" name="current_password" required></label><br><label>Neues Passwort <input type="password" name="new_password" minlength="12" required></label><br><label>Wiederholen <input type="password" name="confirm_password" minlength="12" required></label><br><button>Speichern</button></form><p><a href="formular.php">Zurück</a></p></body></html>
