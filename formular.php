<?php

require __DIR__ . '/auth.php';

migrate_legacy_data();
require_login();
?>
<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Signage – Zentrale Display-Steuerung</title>
    <link rel="stylesheet" href="ui.css">
</head>
<body class="page-admin">
    <main class="page-shell">
        <header class="page-header card">
            <div>
                <h1>Zentrale Display-Steuerung</h1>
            </div>

            <nav class="page-nav">
                <a href="screens.php">Displays</a>
                <a href="change_password.php">Passwort ändern</a>
                <a href="logout.php">Abmelden</a>
            </nav>
        </header>

        <?php if (isset($_GET['message'])): ?>
            <div class="alert alert--success">
                <?= h((string) $_GET['message']) ?>
            </div>
        <?php endif; ?>

        <section class="card stack">
            <h2>Event speichern</h2>

            <form action="upload.php" method="post" enctype="multipart/form-data" class="stack">
                <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">

                <div class="field-grid">
                    <div class="field">
                        <label for="display_typ">Bildschirm</label>
                        <select name="display_typ" id="display_typ" required>
                            <?php foreach (displays() as $screen): ?>
                                <option value="<?= h($screen['id']) ?>">
                                    <?= h($screen['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="field">
                        <label for="start_zeit">Von</label>
                        <input
                            type="datetime-local"
                            name="start_zeit"
                            id="start_zeit"
                            required
                        >
                    </div>

                    <div class="field">
                        <label for="ende_zeit">Bis</label>
                        <input
                            type="datetime-local"
                            name="ende_zeit"
                            id="ende_zeit"
                            required
                        >
                    </div>
                </div>

                <div class="field">
                    <label for="menue_bild">Bild / Grafik (JPG oder MP4)</label>
                    <input
                        type="file"
                        name="menue_bild"
                        id="menue_bild"
                        accept="image/jpeg,.jpg,.jpeg,video/mp4,.mp4"
                        required
                    >
                </div>

                <button type="submit">Event speichern</button>
            </form>
        </section>

        <section class="card stack">
            <h2>Geplante Displays &amp; Programme</h2>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Datei</th>
                            <th>Display</th>
                            <th>Von</th>
                            <th>Bis</th>
                            <th>Aktion</th>
                        </tr>
                    </thead>
                    <tbody id="events">
                        <tr>
                            <td colspan="5">Lade Events…</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <script>
        const csrf = <?= json_encode(csrf_token()) ?>;
        const pad = (n) => String(n).padStart(2, '0');
        const local = (d) =>
            `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;

        const now = new Date();
        const tomorrow = new Date(now);

        tomorrow.setDate(now.getDate() + 1);

        document.querySelector('#start_zeit').value = local(
            new Date(
                now.getFullYear(),
                now.getMonth(),
                now.getDate(),
                12
            )
        );

        document.querySelector('#ende_zeit').value = local(
            new Date(
                tomorrow.getFullYear(),
                tomorrow.getMonth(),
                tomorrow.getDate(),
                8
            )
        );

        function cell(text) {
            const td = document.createElement('td');
            td.textContent = text;
            return td;
        }

        async function load() {
            try {
                const response = await fetch('events.php', {
                    cache: 'no-store',
                });

                if (!response.ok) {
                    throw Error();
                }

                const list = await response.json();
                const body = document.querySelector('#events');

                body.replaceChildren();
                list.sort((a, b) => a.start.localeCompare(b.start));

                if (!list.length) {
                    body.innerHTML = '<tr><td colspan="5">Aktuell keine Events geplant.</td></tr>';
                    return;
                }

                for (const e of list) {
                    const tr = document.createElement('tr');
                    tr.append(
                        cell(e.original_name || 'Datei'),
                        cell(e.display),
                        cell(e.start),
                        cell(e.ende)
                    );

                    const actions = document.createElement('td');
                    const form = document.createElement('form');
                    form.method = 'post';
                    form.action = 'upload.php';
                    form.className = 'inline-form';

                    for (const [name, value] of Object.entries({
                        csrf,
                        action: 'delete',
                        loesch_bild: e.bild,
                    })) {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = name;
                        input.value = value;
                        form.append(input);
                    }

                    const button = document.createElement('button');
                    button.textContent = 'Löschen';
                    button.className = 'btn btn-danger';
                    button.onclick = () => confirm('Dieses Event löschen?');
                    form.append(button);

                    actions.append(form);
                    tr.append(actions);
                    body.append(tr);
                }
            } catch {
                document.querySelector('#events').innerHTML = '<tr><td colspan="5">Events konnten nicht geladen werden.</td></tr>';
            }
        }

        load();
    </script>
</body>
</html>
