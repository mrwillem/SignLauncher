<?php

require __DIR__ . '/auth.php';

migrate_legacy_data();

if (!is_configured()) {
    header('Location: setup.php');
    exit;
}

if (logged_in()) {
    header('Location: formular.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $password = (string) ($_POST['password'] ?? '');
    $allowed = with_data_lock(function () use ($password): bool {
        $data = auth_data();
        if (login_rate_limited($data)) {
            return false;
        }

        $hash = $data['password_hash'] ?? '';
        if (is_string($hash) && password_verify($password, $hash)) {
            clear_failed_logins($data);
            save_auth_data($data);
            return true;
        }

        record_failed_login($data);
        save_auth_data($data);
        return false;
    });

    if ($allowed) {
        login_user();
        header('Location: formular.php');
        exit;
    }

    $error = 'Anmeldung fehlgeschlagen oder vorübergehend gesperrt.';
}
?>
<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Signage anmelden</title>
    <link rel="stylesheet" href="ui.css">
</head>
<body class="page-auth">
    <main class="auth-shell card stack">
        <div>
            <h1>Signage-Verwaltung</h1>
            <p class="helper">Melden Sie sich mit dem Administratorkennwort an.</p>
        </div>

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
                    autocomplete="current-password"
                    required
                >
            </div>

            <button type="submit">Anmelden</button>
        </form>

        <div class="auth-actions">
            <a
                href="reset_password.php"
                class="btn btn-secondary"
            >
                Passwort mit Wiederherstellungscode zurücksetzen
            </a>
        </div>
    </main>
</body>
</html>
