<?php
require_once 'config.php';

$action = isset($_GET['action']) ? $_GET['action'] : '';

// REGISTER
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'register') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['email']) || empty($data['password']) || empty($data['full_name'])) {
        echo json_encode(['error' => 'All fields are required']);
        exit;
    }
    
    // Check if email exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$data['email']]);
    if ($stmt->fetch()) {
        echo json_encode(['error' => 'Email already registered']);
        exit;
    }
    
    // Hash password and save
    $hashed = password_hash($data['password'], PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (email, password, full_name, phone) VALUES (?, ?, ?, ?)");
    $result = $stmt->execute([$data['email'], $hashed, $data['full_name'], $data['phone'] ?? '']);
    
    if ($result) {
        $user_id = $pdo->lastInsertId();
        $stmt = $pdo->prepare("SELECT id, email, full_name, phone, role FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        echo json_encode(['success' => true, 'message' => 'Registration successful', 'user' => $user]);
    } else {
        echo json_encode(['error' => 'Registration failed']);
    }
    exit;
}

// LOGIN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'login') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['email']) || empty($data['password'])) {
        echo json_encode(['error' => 'Email and password required']);
        exit;
    }
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$data['email']]);
    $user = $stmt->fetch();
    
    if (!$user || !password_verify($data['password'], $user['password'])) {
        echo json_encode(['error' => 'Invalid credentials']);
        exit;
    }
    
    unset($user['password']);
    echo json_encode(['success' => true, 'message' => 'Login successful', 'user' => $user]);
    exit;
}

// GET USER
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'me') {
    $user_id = isset($_GET['user_id']) ? $_GET['user_id'] : null;
    
    if (!$user_id) {
        echo json_encode(['error' => 'User ID required']);
        exit;
    }
    
    $stmt = $pdo->prepare("SELECT id, email, full_name, phone, role FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if ($user) {
        echo json_encode(['success' => true, 'user' => $user]);
    } else {
        echo json_encode(['error' => 'User not found']);
    }
    exit;
}

echo json_encode(['error' => 'Invalid request']);
?>