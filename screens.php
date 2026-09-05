<?php

require __DIR__ . '/auth.php';

require_login();

$error = '';
$edit = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $action = (string) ($_POST['action'] ?? '');
    $id = (string) ($_POST['id'] ?? '');

    if (!display_id_valid($id)) {
        $error = 'ID must contain only letters, numbers, hyphens, and underscores.';
    } else {
        with_data_lock(function () use ($action, $id, &$error): void {
            $all = displays();
            $index = array_search($id, array_column($all, 'id'), true);

            if ($action === 'delete') {
                if ($index === false) {
                    $error = 'Display not found.';
                    return;
                }

                foreach (events() as $event) {
                    if (($event['display'] ?? '') === $id) {
                        $error = 'Delete this display’s scheduled events first.';
                        return;
                    }
                }

                array_splice($all, $index, 1);
                save_displays($all);
                return;
            }

            $name = trim((string) ($_POST['name'] ?? ''));
            $orientation = filter_var(
                $_POST['orientation'] ?? null,
                FILTER_VALIDATE_INT
            );

            if (
                $name === '' ||
                $orientation === false ||
                !in_array($orientation, [0, 90, 180, 270], true)
            ) {
                $error = 'Name and orientation are required.';
                return;
            }

            $original = (string) ($_POST['original_id'] ?? '');

            if ($original !== '' && $original !== $id) {
                $error = 'Display IDs cannot be changed.';
                return;
            }

            if ($index !== false) {
                $all[$index] = [
                    'id' => $id,
                    'name' => $name,
                    'orientation' => $orientation,
                ];
            } else {
                $all[] = [
                    'id' => $id,
                    'name' => $name,
                    'orientation' => $orientation,
                ];
            }

            save_displays($all);
        });
    }

    if ($error === '') {
        header('Location: screens.php');
        exit;
    }
}

if (isset($_GET['edit'])) {
    $edit = display_by_id((string) $_GET['edit']);
}

$form = $edit ?? [
    'id' => '',
    'name' => '',
    'orientation' => 0,
];

$baseUrl = 'https://' . $_SERVER['HTTP_HOST'];
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Displays</title>
    <link rel="stylesheet" href="ui.css">
</head>
<body class="page-admin">
    <main class="page-shell">
        <header class="page-header card">
            <div>
                <h1>Displays</h1>
            </div>

            <nav class="page-nav">
                <a href="formular.php" class="btn btn-secondary">
                    Content administration
                </a>
            </nav>
        </header>

        <?php if ($error): ?>
            <div class="alert alert--error">
                <?= h($error) ?>
            </div>
        <?php endif; ?>

        <section class="card stack">
            <h2>
                <?= $edit ? 'Edit display' : 'Create display' ?>
            </h2>

            <form method="post" class="stack">
                <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="original_id" value="<?= h($edit['id'] ?? '') ?>">

                <div class="field-grid">
                    <div class="field">
                        <label for="id">ID</label>
                        <input
                            id="id"
                            name="id"
                            pattern="[A-Za-z0-9_-]+"
                            value="<?= h($form['id']) ?>"
                            <?= $edit ? 'readonly' : '' ?>
                            required
                        >
                    </div>

                    <div class="field">
                        <label for="name">Name</label>
                        <input
                            id="name"
                            name="name"
                            value="<?= h($form['name']) ?>"
                            required
                        >
                    </div>

                    <div class="field">
                        <label for="orientation">Orientation</label>
                        <select name="orientation" id="orientation">
                            <?php foreach ([0, 90, 180, 270] as $o): ?>
                                <option
                                    value="<?= $o ?>"
                                    <?= $form['orientation'] === $o ? 'selected' : '' ?>
                                >
                                    <?= $o ?>°
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit">Save display</button>

                    <?php if ($edit): ?>
                        <a href="screens.php" class="btn btn-secondary">Cancel</a>
                    <?php endif; ?>
                </div>
            </form>
        </section>

        <section class="card stack">
            <h2>Configured displays</h2>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Orientation</th>
                            <th>Playback URL</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (displays() as $display): ?>
                            <?php
                            $playbackUrl = $baseUrl
                                . '/display.php?screen='
                                . rawurlencode($display['id']);
                            ?>
                            <tr>
                                <td>
                                    <?= h($display['id']) ?>
                                </td>
                                <td>
                                    <?= h($display['name']) ?>
                                </td>
                                <td>
                                    <?= (int) $display['orientation'] ?>°
                                </td>
                                <td>
                                    <a
                                        href="<?= h($playbackUrl) ?>"
                                        target="_blank"
                                        class="btn btn-secondary"
                                    >
                                        View
                                    </a>

                                    <br>

                                    <code>
                                        <?= h($playbackUrl) ?>
                                    </code>
                                </td>
                                <td>
                                    <div class="actions">
                                        <a
                                            href="screens.php?edit=<?= rawurlencode($display['id']) ?>"
                                            class="btn btn-secondary"
                                        >
                                            Edit
                                        </a>

                                        <form
                                            method="post"
                                            class="inline-form"
                                            onsubmit="return confirm('Delete this display?');"
                                        >
                                            <input
                                                type="hidden"
                                                name="csrf"
                                                value="<?= h(csrf_token()) ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="action"
                                                value="delete"
                                            >

                                            <input
                                                type="hidden"
                                                name="id"
                                                value="<?= h($display['id']) ?>"
                                            >

                                            <button
                                                type="submit"
                                                class="btn btn-danger"
                                            >
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>
