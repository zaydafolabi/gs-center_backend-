<?php
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
$id = isset($_GET['id']) ? $_GET['id'] : null;

// GET products
if ($method === 'GET') {
    if ($id) {
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$id]);
        $product = $stmt->fetch();
        
        if ($product) {
            echo json_encode(['success' => true, 'product' => $product]);
        } else {
            echo json_encode(['error' => 'Product not found']);
        }
    } else {
        $featured = isset($_GET['featured']) && $_GET['featured'] == 'true';
        
        if ($featured) {
            $stmt = $pdo->prepare("SELECT * FROM products WHERE is_featured = 1 AND stock > 0 ORDER BY id DESC");
        } else {
            $stmt = $pdo->prepare("SELECT * FROM products WHERE stock > 0 ORDER BY id DESC");
        }
        
        $stmt->execute();
        $products = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'products' => $products]);
    }
    exit;
}

// POST - Create product
if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['name']) || empty($data['price'])) {
        echo json_encode(['error' => 'Name and price required']);
        exit;
    }
    
    $stmt = $pdo->prepare("
        INSERT INTO products (name, description, price, stock, image_url, is_featured) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    $result = $stmt->execute([
        $data['name'],
        $data['description'] ?? '',
        $data['price'],
        $data['stock'] ?? 0,
        $data['image_url'] ?? '',
        $data['is_featured'] ?? 0
    ]);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Product created', 'id' => $pdo->lastInsertId()]);
    } else {
        echo json_encode(['error' => 'Failed to create product']);
    }
    exit;
}

echo json_encode(['error' => 'Invalid request']);
?>