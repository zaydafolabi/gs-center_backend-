<?php
header('Content-Type: application/json');
echo json_encode([
    'DB_HOST' => getenv('DB_HOST') ?: 'not set',
    'DB_PORT' => getenv('DB_PORT') ?: 'not set',
    'DB_NAME' => getenv('DB_NAME') ?: 'not set',
    'DB_USER' => getenv('DB_USER') ?: 'not set',
    'has_password' => !empty(getenv('DB_PASSWORD')),
    'raw_env' => $_ENV
]);
?>
