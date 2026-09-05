<?php

require __DIR__ . '/auth.php';

migrate_legacy_data();

if (!is_configured()) {
    header('Location: setup.php');
    exit;
}

$error = '';

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
?>
<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Passwort zurücksetzen</title>
    <link rel="stylesheet" href="ui.css">
</head>
<body class="page-auth">
    <main class="auth-shell card stack">
        <div>
            <h1>Passwort zurücksetzen</h1>
        </div>

        <?php if ($error): ?>
            <div class="alert alert--error">
                <?= h($error) ?>
            </div>
        <?php endif; ?>

        <form method="post" class="stack">
            <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">

            <div class="field">
                <label for="recovery_code">Wiederherstellungscode</label>
                <input
                    type="password"
                    name="recovery_code"
                    id="recovery_code"
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

            <button type="submit">Passwort zurücksetzen</button>
        </form>

        <div class="auth-actions">
            <a href="login.php">Zur Anmeldung</a>
        </div>
    </main>
</body>
</html>
