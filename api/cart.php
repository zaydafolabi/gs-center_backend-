<?php
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];

// GET - Load user's cart
if ($method === 'GET') {
    $user_id = isset($_GET['user_id']) ? $_GET['user_id'] : null;
    
    if (!$user_id) {
        echo json_encode(['error' => 'User ID required']);
        exit;
    }
    
    // Get cart items from database
    $stmt = $pdo->prepare("
        SELECT c.*, p.name, p.price, p.image_url 
        FROM cart c
        JOIN products p ON c.product_id = p.id
        WHERE c.user_id = ?
    ");
    $stmt->execute([$user_id]);
    $items = $stmt->fetchAll();
    
    echo json_encode(['success' => true, 'cartItems' => $items]);
    exit;
}

// POST - Save cart
if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['user_id']) || !isset($data['cart_items'])) {
        echo json_encode(['error' => 'User ID and cart items required']);
        exit;
    }
    
    $user_id = $data['user_id'];
    $cart_items = $data['cart_items'];
    
    try {
        // Delete existing cart items
        $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
        $stmt->execute([$user_id]);
        
        // Insert new cart items
        foreach ($cart_items as $item) {
            $stmt = $pdo->prepare("
                INSERT INTO cart (user_id, product_id, quantity) 
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$user_id, $item['id'], $item['quantity']]);
        }
        
        echo json_encode(['success' => true, 'message' => 'Cart saved']);
    } catch (Exception $e) {
        echo json_encode(['error' => 'Failed to save cart: ' . $e->getMessage()]);
    }
    exit;
}

echo json_encode(['error' => 'Invalid request']);
?>