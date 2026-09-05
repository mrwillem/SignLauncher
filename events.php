<?php

require __DIR__ . '/auth.php';
migrate_legacy_data();
require_login();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
echo json_encode(events(), JSON_UNESCAPED_SLASHES);
