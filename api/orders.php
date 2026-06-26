<?php
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];

// POST - Create order
if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['user_id']) || empty($data['cart_items']) || !isset($data['total'])) {
        echo json_encode(['error' => 'User ID, cart items, and total required']);
        exit;
    }
    
    $user_id = $data['user_id'];
    $cart_items = $data['cart_items'];
    $total = $data['total'];
    
    try {
        // Generate order number
        $order_number = 'ORD-' . date('Ymd') . '-' . rand(1000, 9999);
        
        // Start transaction
        $pdo->beginTransaction();
        
        // Insert order
        $stmt = $pdo->prepare("
            INSERT INTO orders (user_id, order_number, total_amount, grand_total, status, created_at) 
            VALUES (?, ?, ?, ?, 'pending', NOW())
        ");
        $stmt->execute([$user_id, $order_number, $total, $total]);
        $order_id = $pdo->lastInsertId();
        
        // Insert order items
        foreach ($cart_items as $item) {
            $price = is_string($item['price']) ? floatval($item['price']) : $item['price'];
            $stmt = $pdo->prepare("
                INSERT INTO order_items (order_id, product_id, quantity, unit_price, total_price) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $order_id, 
                $item['id'], 
                $item['quantity'], 
                $price, 
                $price * $item['quantity']
            ]);
            
            // Update product stock
            $stmt = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
            $stmt->execute([$item['quantity'], $item['id']]);
        }
        
        // Clear user's cart
        $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
        $stmt->execute([$user_id]);
        
        // Commit transaction
        $pdo->commit();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Order placed successfully',
            'order_number' => $order_number,
            'order_id' => $order_id
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['error' => 'Failed to create order: ' . $e->getMessage()]);
    }
    exit;
}

// GET - Get user's orders
if ($method === 'GET') {
    $user_id = isset($_GET['user_id']) ? $_GET['user_id'] : null;
    
    if (!$user_id) {
        echo json_encode(['error' => 'User ID required']);
        exit;
    }
    
    $stmt = $pdo->prepare("
        SELECT o.*, 
               (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count
        FROM orders o
        WHERE o.user_id = ?
        ORDER BY o.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $orders = $stmt->fetchAll();
    
    // Get items for each order
    foreach ($orders as &$order) {
        $stmt = $pdo->prepare("
            SELECT oi.*, p.name 
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            WHERE oi.order_id = ?
        ");
        $stmt->execute([$order['id']]);
        $order['items'] = $stmt->fetchAll();
    }
    
    echo json_encode(['success' => true, 'orders' => $orders]);
    exit;
}

echo json_encode(['error' => 'Invalid request']);
?>