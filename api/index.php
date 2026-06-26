<?php
// This file tells PHP where to route requests
// If a file exists, serve it directly
if (file_exists($_SERVER['DOCUMENT_ROOT'] . $_SERVER['REQUEST_URI'])) {
    return false;
}

// Otherwise, route to the requested PHP file
$path = $_SERVER['REQUEST_URI'];
$path = str_replace('/backend/api/', '', $path);
$file = __DIR__ . '/' . $path;

if (file_exists($file)) {
    require $file;
} else {
    http_response_code(404);
    echo json_encode(['error' => 'Not found']);
}
?>