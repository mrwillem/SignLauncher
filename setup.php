<?php

require __DIR__ . '/auth.php';

migrate_legacy_data();

if (is_configured()) {
    header('Location: login.php');
    exit;
}

$error = '';
$recoveryCode = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $password = (string) ($_POST['password'] ?? '');
    $confirm = (string) ($_POST['confirm_password'] ?? '');

    if (!valid_password($password)) {
        $error = 'Das Passwort muss mindestens 12 Zeichen lang sein.';
    } elseif (!hash_equals($password, $confirm)) {
        $error = 'Die Passwörter stimmen nicht überein.';
    } else {
        $recoveryCode = new_recovery_code();
        save_auth_data([
            'password_hash' => password_hash($password, PASSWORD_ARGON2ID),
            'recovery_hash' => recovery_code_hash($recoveryCode),
        ]);
    }
}
?>
<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Signage einrichten</title>
    <link rel="stylesheet" href="ui.css">
</head>
<body class="page-auth">
    <main class="auth-shell card stack">
        <div>
            <h1>Signage einrichten</h1>
            <p class="helper">Erstellen Sie das Administrator-Passwort. Das frühere gemeinsame Passwort wird nicht übernommen.</p>
        </div>

        <?php if ($recoveryCode !== null): ?>
            <div class="alert alert--success">
                Einrichtung abgeschlossen. Bewahren Sie diesen Wiederherstellungscode sicher auf; er wird nur jetzt angezeigt:
            </div>

            <code class="code-block"><?= h($recoveryCode) ?></code>

            <div class="auth-actions">
                <a href="login.php" class="btn btn-secondary">Zur Anmeldung</a>
            </div>
        <?php else: ?>
            <?php if ($error): ?>
                <div class="alert alert--error">
                    <?= h($error) ?>
                </div>
            <?php endif; ?>

            <form method="post" class="stack">
                <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">

                <div class="field">
                    <label for="password">Passwort</label>
                    <input
                        type="password"
                        name="password"
                        id="password"
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

                <button type="submit">Einrichten</button>
            </form>
        <?php endif; ?>
    </main>
</body>
</html>
