<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
echo json_encode([
    'status' => 'online',
    'message' => 'GS Center API is running successfully.',
    'version' => '1.0.0',
    'endpoints' => [
        'database_setup' => '/api/setup_db.php',
        'products' => '/api/products.php',
        'services' => '/api/services.php'
    ]
], JSON_PRETTY_PRINT);
?>
