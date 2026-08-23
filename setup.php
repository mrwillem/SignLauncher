<?php
require __DIR__ . '/auth.php';
migrate_legacy_data();
if (is_configured()) { header('Location: login.php'); exit; }
$error = '';
$recoveryCode = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $password = (string) ($_POST['password'] ?? '');
    $confirm = (string) ($_POST['confirm_password'] ?? '');
    if (!valid_password($password)) $error = 'Das Passwort muss mindestens 12 Zeichen lang sein.';
    elseif (!hash_equals($password, $confirm)) $error = 'Die Passwörter stimmen nicht überein.';
    else {
        $recoveryCode = new_device_token();
        save_auth_data(['password_hash' => password_hash($password, PASSWORD_ARGON2ID), 'recovery_hash' => token_hash($recoveryCode), 'screens' => []]);
    }
}
?><!doctype html><html lang="de"><meta charset="utf-8"><title>Signage einrichten</title><body>
<h1>Signage einrichten</h1>
<?php if ($recoveryCode !== null): ?><p>Einrichtung abgeschlossen. Bewahren Sie diesen Wiederherstellungscode sicher auf; er wird nur jetzt angezeigt:</p><p><code><?= h($recoveryCode) ?></code></p><p><a href="login.php">Zur Anmeldung</a></p>
<?php else: ?><p>Erstellen Sie das Administrator-Passwort. Das frühere gemeinsame Passwort wird nicht übernommen.</p><?php if ($error): ?><p><?= h($error) ?></p><?php endif; ?><form method="post"><input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>"><label>Passwort <input type="password" name="password" minlength="12" required></label><br><label>Wiederholen <input type="password" name="confirm_password" minlength="12" required></label><br><button>Einrichten</button></form><?php endif; ?>
</body></html>
