<?php
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

// GET appointments
if ($method === 'GET') {
    $user_id = isset($_GET['user_id']) ? $_GET['user_id'] : null;
    
    // Get available time slots
    if ($action === 'available') {
        $date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
        $slots = [];
        
        for ($hour = 9; $hour <= 17; $hour++) {
            $time = sprintf("%02d:00:00", $hour);
            
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM appointments 
                                   WHERE appointment_date = ? AND appointment_time = ? AND status != 'cancelled'");
            $stmt->execute([$date, $time]);
            $booked = $stmt->fetch()['count'];
            
            if ($booked == 0) {
                $slots[] = $time;
            }
        }
        
        echo json_encode(['success' => true, 'slots' => $slots]);
        exit;
    }
    
    // Get user's appointments
    if ($user_id) {
        $stmt = $pdo->prepare("
            SELECT a.*, s.name as service_name, s.price 
            FROM appointments a
            JOIN services s ON a.service_id = s.id
            WHERE a.user_id = ?
            ORDER BY a.appointment_date DESC
        ");
        $stmt->execute([$user_id]);
        $appointments = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'appointments' => $appointments]);
        exit;
    }
    
    echo json_encode(['error' => 'User ID required']);
    exit;
}

// POST - Book appointment
if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['user_id']) || empty($data['service_id']) || 
        empty($data['appointment_date']) || empty($data['appointment_time'])) {
        echo json_encode(['error' => 'All fields required']);
        exit;
    }
    
    // Check if slot is taken
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM appointments 
                           WHERE appointment_date = ? AND appointment_time = ? AND status != 'cancelled'");
    $stmt->execute([$data['appointment_date'], $data['appointment_time']]);
    $booked = $stmt->fetch()['count'];
    
    if ($booked > 0) {
        echo json_encode(['error' => 'Time slot already booked']);
        exit;
    }
    
    // Book the appointment
    $stmt = $pdo->prepare("
        INSERT INTO appointments (user_id, service_id, appointment_date, appointment_time, notes, status) 
        VALUES (?, ?, ?, ?, ?, 'pending')
    ");
    
    $result = $stmt->execute([
        $data['user_id'],
        $data['service_id'],
        $data['appointment_date'],
        $data['appointment_time'],
        $data['notes'] ?? ''
    ]);
    
    if ($result) {
        $appointment_id = $pdo->lastInsertId();
        
        $stmt = $pdo->prepare("
            SELECT a.*, s.name as service_name, s.price 
            FROM appointments a
            JOIN services s ON a.service_id = s.id
            WHERE a.id = ?
        ");
        $stmt->execute([$appointment_id]);
        $appointment = $stmt->fetch();
        
        echo json_encode([
            'success' => true,
            'message' => 'Appointment booked!',
            'appointment' => $appointment
        ]);
    } else {
        echo json_encode(['error' => 'Failed to book appointment']);
    }
    exit;
}

// PUT - Cancel appointment
if ($method === 'PUT' && $action === 'cancel') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['appointment_id'])) {
        echo json_encode(['error' => 'Appointment ID required']);
        exit;
    }
    
    $stmt = $pdo->prepare("UPDATE appointments SET status = 'cancelled' WHERE id = ?");
    $result = $stmt->execute([$data['appointment_id']]);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Appointment cancelled']);
    } else {
        echo json_encode(['error' => 'Cancellation failed']);
    }
    exit;
}

echo json_encode(['error' => 'Invalid request']);
?>