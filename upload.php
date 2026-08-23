<?php
// 1. SICHERHEIT: Der perfekt abgestimmte Passwort-Hash für dein System
$storedHash = '$2y$12$7EfSSQd/LU3PjwD3M0bkUOXWUIi9rlLAdnnRgld3icy8GWJ4IZHNa';

if (!isset($_POST['pw']) || !password_verify($_POST['pw'], $storedHash)) {
    http_response_code(403);
    die("<h2>Fehler: Zugriff verweigert. Falsches Passwort!</h2><a href='formular.html'>Zurück</a>");
}

// ==========================================
// AKTION A: EVENT LÖSCHEN (AUS DER TABELLE)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $loeschBild = $_POST['loesch_bild'];
    $jsonDatei = __DIR__ . '/events.json';
    
    if (file_exists($jsonDatei)) {
        $events = json_decode(file_get_contents($jsonDatei), true);
        if (is_array($events)) {
            $neueEvents = [];
            foreach ($events as $event) {
                if ($event['bild'] === $loeschBild) {
                    $bildPfad = __DIR__ . '/' . $event['bild'];
                    if (file_exists($bildPfad) && strpos($event['bild'], 'display_') === 0) {
                        unlink($bildPfad);
                    }
                } else {
                    $neueEvents[] = $event;
                }
            }
            file_put_contents($jsonDatei, json_encode($neueEvents, JSON_PRETTY_PRINT));
        }
    }
    
    echo "<div style='font-family:sans-serif; text-align:center; margin-top:50px;'>";
    echo "<h2 style='color:green;'>Erfolgreich gelöscht!</h2>";
    echo "<p>Das Event und die dazugehörige Bilddatei wurden entfernt.</p>";
    echo "<p style='color:#666; font-size:14px;'>Leite zurück zum Formular...</p>";
    echo "</div>";
    header("Refresh: 2; url=formular.html");
    exit;
}

// ==========================================
// AKTION B: STANDARD-NEU-UPLOAD (VOM FORMULAR)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['menue_bild'])) {
    
    $datei = $_FILES['menue_bild'];
    $display = htmlspecialchars($_POST['display_typ']);
    
    // NEU: Holt den echten Original-Dateinamen (z.B. eisbar_v3.jpg)
    $originalName = htmlspecialchars($datei['name']); 
    
    $start = date('Y-m-d H:i:s', strtotime($_POST['start_zeit']));
    $ende = date('Y-m-d H:i:s', strtotime($_POST['ende_zeit']));

    $ext = strtolower(pathinfo($datei['name'], PATHINFO_EXTENSION));
    if (($ext !== 'jpg' && $ext !== 'jpeg') || $datei['size'] > 5 * 1024 * 1024 || getimagesize($datei['tmp_name']) === false) {
        die("<h2>Fehler: Ungültiges oder zu großes Bild! Nur JPGs bis 5MB erlaubt.</h2>");
    }

    $ziel_dateiname = 'display_' . $display . '_' . time() . '.jpg';

    if (move_uploaded_file($datei['tmp_name'], __DIR__ . '/' . $ziel_dateiname)) {
        
        $jsonDatei = __DIR__ . '/events.json';
        $aktuelleEvents = [];

        if (file_exists($jsonDatei)) {
            $aktuelleEvents = json_decode(file_get_contents($jsonDatei), true);
            if (!is_array($aktuelleEvents)) $aktuelleEvents = [];
        }

        // On-Demand Auto-Clean für abgelaufene Sachen
        $gesaeuberteEvents = [];
        $jetztZeitstempel = time();

        foreach ($aktuelleEvents as $altesEvent) {
            $endeZeitstempel = strtotime($altesEvent['ende']);
            if ($endeZeitstempel < ($jetztZeitstempel - 86400)) {
                $altesBildPfad = __DIR__ . '/' . $altesEvent['bild'];
                if (file_exists($altesBildPfad) && strpos($altesEvent['bild'], 'display_') === 0) {
                    unlink($altesBildPfad);
                }
            } else {
                $gesaeuberteEvents[] = $altesEvent;
            }
        }

        // Speichert den originalen Namen ('original_name') mit ab
        $gesaeuberteEvents[] = [
            'original_name' => $originalName,
            'display'       => $display,
            'start'         => $start,
            'ende'          => $ende,
            'bild'          => $ziel_dateiname
        ];

        file_put_contents($jsonDatei, json_encode($gesaeuberteEvents, JSON_PRETTY_PRINT));

        echo "<div style='font-family:sans-serif; text-align:center; margin-top:50px;'>";
        echo "<h2 style='color:green;'>Erfolgreich gespeichert!</h2>";
        echo "<p>Das Bild für das <strong>" . strtoupper($display) . "</strong>-Display wurde erfolgreich eingeplant.</p>";
        echo "<p style='color:#666; font-size:14px;'>Leite zurück zum Formular...</p>";
        echo "</div>";
        header("Refresh: 2; url=formular.html");
    } else {
        die("<h2>Fehler beim Speichern der Datei auf dem Server.</h2>");
    }
}
?>

