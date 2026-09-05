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
                <a href="screens.php" class="btn btn-secondary">Displays</a>
                <a href="change_password.php" class="btn btn-secondary">
                    Passwort ändern
                </a>
                <a href="logout.php" class="btn btn-secondary">Abmelden</a>
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

    <div
        id="eventPreview"
        class="event-preview"
        aria-hidden="true"
    ></div>

    <script>
        const csrf = <?= json_encode(csrf_token()) ?>;
        const preview = document.getElementById('eventPreview');
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

        function previewCell(event) {
            const td = document.createElement('td');
            const trigger = document.createElement('span');

            trigger.textContent = event.original_name || 'Datei';
            trigger.setAttribute('data-preview-display', event.display);
            trigger.setAttribute('data-preview-file', event.bild);
            trigger.setAttribute(
                'data-preview-type',
                /\.mp4$/i.test(event.bild) ? 'video' : 'image'
            );

            trigger.onmouseover = function() {
                showPreview(this);
            };

            trigger.onmouseout = function() {
                hidePreview();
            };

            td.appendChild(trigger);
            return td;
        }

        function previewUrl(display, file) {
            return 'media.php?screen=' + encodeURIComponent(display) + '&file=' + encodeURIComponent(file);
        }

        function stopPreviewMedia() {
            const existingVideo = preview.querySelector('video');

            if (existingVideo) {
                try {
                    existingVideo.pause();
                } catch (e) {
                }

                existingVideo.removeAttribute('src');

                try {
                    existingVideo.load();
                } catch (e) {
                }
            }
        }

        function hidePreview() {
            stopPreviewMedia();
            preview.innerHTML = '';
            preview.style.display = 'none';
            preview.setAttribute('aria-hidden', 'true');
        }

        function positionPreview(trigger) {
            const rect = trigger.getBoundingClientRect();
            const margin = 12;
            const previewWidth = 320;
            const previewHeight = 180;
            let left = rect.right + margin;
            let top = rect.top;

            if (left + previewWidth > window.innerWidth - 8) {
                left = rect.left - previewWidth - margin;
            }

            if (left < 8) {
                left = 8;
            }

            if (top + previewHeight > window.innerHeight - 8) {
                top = window.innerHeight - previewHeight - 8;
            }

            if (top < 8) {
                top = 8;
            }

            preview.style.left = left + 'px';
            preview.style.top = top + 'px';
        }

        function showPreview(trigger) {
            const display = trigger.getAttribute('data-preview-display');
            const file = trigger.getAttribute('data-preview-file');
            const type = trigger.getAttribute('data-preview-type');
            const url = previewUrl(display, file);
            let media;

            hidePreview();

            if (type === 'video') {
                media = document.createElement('video');
                media.muted = true;
                media.autoplay = true;
                media.loop = true;
                media.setAttribute('playsinline', 'playsinline');
                media.src = url;

                try {
                    media.play();
                } catch (e) {
                }
            } else {
                media = document.createElement('img');
                media.src = url;
                media.alt = 'Vorschau';
            }

            preview.appendChild(media);
            preview.style.display = 'block';
            preview.setAttribute('aria-hidden', 'false');
            positionPreview(trigger);
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
                        previewCell(e),
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
