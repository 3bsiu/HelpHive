<?php
header('Content-Type: application/json');
require_once '../database/connection.php';
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

session_start();

try {
    $db = getDB();
    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET') {
        $user_id = $_GET['user_id'] ?? null;
        $username = $_GET['username'] ?? null;
        $status = $_GET['status'] ?? 'all';
        $category = $_GET['category'] ?? 'all';

        $query = "SELECT ticket_id as id, username as user, category, subject, description, status, 
                  priority, contact_email, contact_phone, admin_notes, DATE(created_at) as date 
                  FROM tickets WHERE 1=1";
        $params = [];

        if ($user_id) {
            $query .= " AND user_id = ?";
            $params[] = $user_id;
        }
        if ($username) {
            $query .= " AND username = ?";
            $params[] = $username;
        }
        if ($status !== 'all') {
            $query .= " AND status = ?";
            $params[] = $status;
        }
        if ($category !== 'all') {
            $query .= " AND category = ?";
            $params[] = $category;
        }

        $query .= " ORDER BY created_at DESC";

        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $tickets = $stmt->fetchAll();

        echo json_encode([
            'success' => true,
            'tickets' => $tickets
        ]);

    } elseif ($method === 'POST') {
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Not authenticated']);
            exit();
        }

        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['category']) || !isset($data['subject']) || !isset($data['description'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
            exit();
        }

        $user_id = $_SESSION['user_id'];
        $username = $_SESSION['username'];
        $priority = $data['priority'] ?? 'Medium';
        $contact_email = $data['contact_email'] ?? null;
        $contact_phone = $data['contact_phone'] ?? null;

        $stmt = $db->prepare("SELECT COUNT(*) as count FROM tickets");
        $stmt->execute();
        $count = $stmt->fetch()['count'];
        $ticket_id = 'TKT-' . str_pad($count + 1, 3, '0', STR_PAD_LEFT);

        $stmt = $db->prepare("
            INSERT INTO tickets (ticket_id, user_id, username, category, subject, description, status, priority, contact_email, contact_phone)
            VALUES (?, ?, ?, ?, ?, ?, 'Open', ?, ?, ?)
        ");
        $stmt->execute([
            $ticket_id,
            $user_id,
            $username,
            $data['category'],
            $data['subject'],
            $data['description'],
            $priority,
            $contact_email,
            $contact_phone
        ]);

        echo json_encode([
            'success' => true,
            'ticket_id' => $ticket_id,
            'message' => 'Ticket created successfully'
        ]);

    } elseif ($method === 'PUT') {
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Admin access required']);
            exit();
        }

        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['ticket_id']) || !isset($data['status'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
            exit();
        }

        $status = $data['status'];
        $priority = $data['priority'] ?? null;
        $admin_notes = $data['admin_notes'] ?? null;
        $ticket_id = $data['ticket_id'];

        $update_fields = ['status = ?'];
        $params = [$status];

        if ($priority !== null) {
            $update_fields[] = 'priority = ?';
            $params[] = $priority;
        }

        if ($admin_notes !== null) {
            $update_fields[] = 'admin_notes = ?';
            $params[] = $admin_notes;
        }

        $params[] = $ticket_id;
        $query = "UPDATE tickets SET " . implode(', ', $update_fields) . " WHERE ticket_id = ?";
        
        $stmt = $db->prepare($query);
        $stmt->execute($params);

        echo json_encode([
            'success' => true,
            'message' => 'Ticket updated successfully'
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>

