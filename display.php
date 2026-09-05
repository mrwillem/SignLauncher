<?php

require __DIR__ . '/auth.php';

$screen = (string) ($_GET['screen'] ?? '');
$display = display_by_id($screen);

if ($display === null) {
    http_response_code(400);
    exit('Unbekanntes Display.');
}
?>
<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Signage Monitor</title>

    <style>
        html,
        body {
            width: 100%;
            height: 100%;
            margin: 0;
            overflow: hidden;
            background: #000;
        }

        #content {
            position: fixed;
            inset: 0;
        }

        #content img,
        #content video {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        #content.orientation-180 {
            transform: rotate(180deg);
        }

        #content.orientation-90,
        #content.orientation-270 {
            width: 100vh;
            height: 100vw;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%) rotate(90deg);
        }

        #content.orientation-270 {
            transform: translate(-50%, -50%) rotate(270deg);
        }
    </style>
</head>
<body>
    <div id="content" class="orientation-<?= (int) $display['orientation'] ?>">
        <img
            id="signageBild"
            src="standard_<?= h($screen) ?>.jpg"
            alt="Signage"
        >

        <video
            id="signageVideo"
            style="display:none"
            muted
            autoplay
            loop
        ></video>
    </div>

    <script>
        var playlistUrl = <?= json_encode('playlist.php?screen=' . rawurlencode($screen)) ?>;
        var currentImage = '';

        function aktualisiereDisplay() {
            var xhr = new XMLHttpRequest();

            xhr.open(
                'GET',
                playlistUrl + '&v=' + new Date().getTime(),
                true
            );

            xhr.onreadystatechange = function() {
                if (xhr.readyState !== 4) {
                    return;
                }

                if (xhr.status < 200 || xhr.status >= 300) {
                    return;
                }

                try {
                    var data = JSON.parse(xhr.responseText);

                    if (data.image !== currentImage) {
                        currentImage = data.image;

                        var image = document.getElementById('signageBild');
                        var video = document.getElementById('signageVideo');

                        var separator =
                            data.image.indexOf('?') >= 0 ? '&' : '?';

                        var url =
                            data.image +
                            separator +
                            'v=' +
                            new Date().getTime();

                        if (data.type === 'video') {
                            image.style.display = 'none';
                            video.style.display = 'block';

                            video.src = url;

                            try {
                                video.play();
                            } catch (e) {
                            }
                        } else {
                            try {
                                video.pause();
                            } catch (e) {
                            }

                            video.style.display = 'none';
                            image.style.display = 'block';
                            image.src = url;
                        }
                    }
                } catch (e) {
                    console.log('Playlist konnte nicht verarbeitet werden');
                }
            };

            xhr.send(null);
        }

        aktualisiereDisplay();

        setInterval(
            aktualisiereDisplay,
            60000
        );
    </script>
</body>
</html>
