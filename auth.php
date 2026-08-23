<?php
declare(strict_types=1);

/*
 * Lightweight application services. Set SIGNLAUNCHER_DATA_DIR to a directory
 * outside the web root in production. The local data/ directory is protected
 * by .htaccess for Apache installations.
 */
const SCREEN_IDS = ['eisbar', 'theke', 'food', 'eingang', 'stehle'];
const SESSION_NAME = 'signlauncher_session';

date_default_timezone_set(getenv('SIGNLAUNCHER_TIMEZONE') ?: 'Europe/Berlin');

function app_data_dir(): string {
    $configured = getenv('SIGNLAUNCHER_DATA_DIR');
    return $configured !== false && $configured !== '' ? rtrim($configured, '/') : __DIR__ . '/data';
}

function app_path(string $name): string {
    return app_data_dir() . '/' . $name;
}

function ensure_data_dir(): void {
    $dir = app_data_dir();
    if (!is_dir($dir) && !mkdir($dir, 0700, true) && !is_dir($dir)) {
        throw new RuntimeException('Das Datenverzeichnis konnte nicht erstellt werden.');
    }
}

function read_json_file(string $path, array $fallback = []): array {
    if (!is_file($path)) return $fallback;
    $decoded = json_decode((string) file_get_contents($path), true);
    return is_array($decoded) ? $decoded : $fallback;
}

function write_json_file(string $path, array $data): void {
    $temporary = $path . '.tmp.' . bin2hex(random_bytes(6));
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    if (file_put_contents($temporary, $json, LOCK_EX) === false || !rename($temporary, $path)) {
        @unlink($temporary);
        throw new RuntimeException('Daten konnten nicht sicher gespeichert werden.');
    }
    @chmod($path, 0600);
}

function with_data_lock(callable $operation): mixed {
    ensure_data_dir();
    $lock = fopen(app_path('.lock'), 'c');
    if ($lock === false || !flock($lock, LOCK_EX)) throw new RuntimeException('Datenspeicher ist nicht verfügbar.');
    try { return $operation(); }
    finally { flock($lock, LOCK_UN); fclose($lock); }
}

function migrate_legacy_data(): void {
    ensure_data_dir();
    $legacyEvents = __DIR__ . '/events.json';
    $eventsPath = app_path('events.json');
    if (!is_file($eventsPath) && is_file($legacyEvents)) {
        $events = read_json_file($legacyEvents);
        foreach ($events as $event) {
            $file = basename((string) ($event['bild'] ?? ''));
            $old = __DIR__ . '/' . $file;
            $new = app_path('media/' . $file);
            if ($file !== '' && is_file($old)) {
                if (!is_dir(app_path('media'))) mkdir(app_path('media'), 0700, true);
                rename($old, $new);
            }
        }
        write_json_file($eventsPath, $events);
    }
}

function auth_data(): array { return read_json_file(app_path('auth.json')); }
function save_auth_data(array $data): void { write_json_file(app_path('auth.json'), $data); }
function is_configured(): bool { return isset(auth_data()['password_hash']); }

function start_secure_session(): void {
    if (session_status() === PHP_SESSION_ACTIVE) return;
    session_name(SESSION_NAME);
    session_set_cookie_params([
        'lifetime' => 0, 'path' => '/', 'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true, 'samesite' => 'Lax'
    ]);
    session_start();
}

function csrf_token(): string {
    start_secure_session();
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}

function require_csrf(): void {
    start_secure_session();
    $token = $_POST['csrf'] ?? '';
    if (!is_string($token) || empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $token)) {
        http_response_code(403); exit('Ungültige Anfrage. Bitte Seite neu laden.');
    }
}

function logged_in(): bool { start_secure_session(); return ($_SESSION['authenticated'] ?? false) === true; }
function require_login(): void {
    if (!logged_in()) { header('Location: login.php'); exit; }
}
function login_user(): void { start_secure_session(); session_regenerate_id(true); $_SESSION['authenticated'] = true; csrf_token(); }
function logout_user(): void {
    start_secure_session(); $_SESSION = []; session_destroy();
    setcookie(session_name(), '', time() - 3600, '/');
}

function valid_password(string $password): bool { return strlen($password) >= 12; }
function valid_screen(string $screen): bool { return in_array($screen, SCREEN_IDS, true); }
function new_device_token(): string { return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '='); }
function token_hash(string $token): string { return hash('sha256', $token); }
function authorize_screen(string $screen, string $token): bool {
    if (!valid_screen($screen) || $token === '') return false;
    $hash = auth_data()['screens'][$screen]['token_hash'] ?? '';
    return is_string($hash) && hash_equals($hash, token_hash($token));
}

function login_attempt_key(): string { return hash('sha256', (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown')); }
function login_rate_limited(array $data): bool {
    $attempts = $data['login_attempts'][login_attempt_key()] ?? [];
    if (!is_array($attempts)) return false;
    return count(array_filter($attempts, static fn($time) => is_int($time) && $time > time() - 900)) >= 5;
}
function record_failed_login(array &$data): void {
    $key = login_attempt_key(); $attempts = $data['login_attempts'][$key] ?? [];
    $data['login_attempts'][$key] = array_values(array_filter($attempts, static fn($time) => is_int($time) && $time > time() - 900));
    $data['login_attempts'][$key][] = time();
}
function clear_failed_logins(array &$data): void { unset($data['login_attempts'][login_attempt_key()]); }

function events(): array { return read_json_file(app_path('events.json')); }
function save_events(array $events): void { write_json_file(app_path('events.json'), $events); }
function media_path(string $file): string { return app_path('media/' . basename($file)); }
function safe_event_file(string $file): bool { return (bool) preg_match('/^display_[a-z]+_[a-zA-Z0-9_-]+\.jpg$/', $file); }
function h(string $value): string { return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
