<?php require __DIR__ . '/auth.php'; $screen = (string) ($_GET['screen'] ?? ''); $token = (string) ($_GET['token'] ?? ''); if (!authorize_screen($screen, $token)) { http_response_code(403); exit('Nicht autorisiertes Display.'); } ?>
<!doctype html><html lang="de"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Signage Monitor</title><style>html,body{margin:0;width:100%;height:100%;background:#000;overflow:hidden}img{width:100%;height:100%;object-fit:cover}</style><body><img id="signageBild" src="standard_<?= h($screen) ?>.jpg" alt="Signage"><script>
const playlistUrl = <?= json_encode('playlist.php?screen=' . rawurlencode($screen) . '&token=' . rawurlencode($token)) ?>;
let currentImage = '';
async function aktualisiereDisplay(){try{const response=await fetch(playlistUrl,{cache:'no-store'});if(!response.ok)throw new Error();const data=await response.json();if(data.image!==currentImage){currentImage=data.image+(data.image.includes('?')?'&':'?')+'v='+Date.now();}}catch(e){console.warn('Playlist nicht erreichbar');}}
aktualisiereDisplay(); setInterval(aktualisiereDisplay,60000);
</script></body></html>
