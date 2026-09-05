<?php

require __DIR__ . '/auth.php';

require_login();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $current = (string) ($_POST['current_password'] ?? '');
    $new = (string) ($_POST['new_password'] ?? '');
    $confirm = (string) ($_POST['confirm_password'] ?? '');
    $data = auth_data();

    if (!password_verify($current, (string) ($data['password_hash'] ?? ''))) {
        $error = 'Das aktuelle Passwort ist falsch.';
    } elseif (!valid_password($new)) {
        $error = 'Das neue Passwort muss mindestens 12 Zeichen lang sein.';
    } elseif (!hash_equals($new, $confirm)) {
        $error = 'Die Passwörter stimmen nicht überein.';
    } else {
        $data['password_hash'] = password_hash($new, PASSWORD_ARGON2ID);
        save_auth_data($data);
        header('Location: formular.php?password=changed');
        exit;
    }
}
?>
<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Passwort ändern</title>
    <link rel="stylesheet" href="ui.css">
</head>
<body class="page-auth">
    <main class="auth-shell card stack">
        <div>
            <h1>Passwort ändern</h1>
        </div>

        <?php if ($error): ?>
            <div class="alert alert--error">
                <?= h($error) ?>
            </div>
        <?php endif; ?>

        <form method="post" class="stack">
            <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">

            <div class="field">
                <label for="current_password">Aktuelles Passwort</label>
                <input
                    type="password"
                    name="current_password"
                    id="current_password"
                    required
                >
            </div>

            <div class="field">
                <label for="new_password">Neues Passwort</label>
                <input
                    type="password"
                    name="new_password"
                    id="new_password"
                    minlength="12"
                    required
                >
            </div>

            <div class="field">
                <label for="confirm_password">Wiederholen</label>
                <input
                    type="password"
                    name="confirm_password"
                    id="confirm_password"
                    minlength="12"
                    required
                >
            </div>

            <button type="submit">Speichern</button>
        </form>

        <div class="auth-actions">
            <a href="formular.php" class="btn btn-secondary">Zurück</a>
        </div>
    </main>
</body>
</html>
