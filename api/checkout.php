<?php
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['user_id']) || empty($data['cart_items'])) {
        echo json_encode(['error' => 'User ID and cart items required']);
        exit;
    }
    
    $user_id = $data['user_id'];
    $cart_items = $data['cart_items'];
    $total = 0;
    
    foreach ($cart_items as $item) {
        $total += $item['price'] * $item['quantity'];
    }
    
    try {
        $pdo->beginTransaction();
        
        // Generate order number
        $order_number = 'ORD-' . date('Ymd') . '-' . rand(1000, 9999);
        
        // Insert order
        $stmt = $pdo->prepare("
            INSERT INTO orders (user_id, order_number, total_amount, grand_total, status, created_at) 
            VALUES (?, ?, ?, ?, 'pending', NOW())
        ");
        $stmt->execute([$user_id, $order_number, $total, $total]);
        $order_id = $pdo->lastInsertId();
        
        // Insert order items
        foreach ($cart_items as $item) {
            $stmt = $pdo->prepare("
                INSERT INTO order_items (order_id, product_id, quantity, unit_price, total_price) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $order_id, 
                $item['id'], 
                $item['quantity'], 
                $item['price'], 
                $item['price'] * $item['quantity']
            ]);
        }
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'order_number' => $order_number,
            'message' => 'Order placed successfully!'
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['error' => 'Checkout failed: ' . $e->getMessage()]);
    }
    exit;
}

echo json_encode(['error' => 'Invalid request']);
?>