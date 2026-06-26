<?php
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
$id = isset($_GET['id']) ? $_GET['id'] : null;

if ($method === 'GET') {
    if ($id) {
        $stmt = $pdo->prepare("SELECT * FROM services WHERE id = ? AND is_active = 1");
        $stmt->execute([$id]);
        $service = $stmt->fetch();
        
        if ($service) {
            echo json_encode(['success' => true, 'service' => $service]);
        } else {
            echo json_encode(['error' => 'Service not found']);
        }
    } else {
        $stmt = $pdo->prepare("SELECT * FROM services WHERE is_active = 1 ORDER BY id");
        $stmt->execute();
        $services = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'services' => $services]);
    }
    exit;
}

echo json_encode(['error' => 'Invalid request']);
?>