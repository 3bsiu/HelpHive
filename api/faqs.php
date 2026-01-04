<?php
header('Content-Type: application/json');
require_once '../database/connection.php';
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
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
        $category = $_GET['category'] ?? 'all';

        $query = "SELECT * FROM faqs WHERE 1=1";
        $params = [];

        if ($category !== 'all') {
            $query .= " AND category = ?";
            $params[] = $category;
        }

        $query .= " ORDER BY category, id";

        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $faqs = $stmt->fetchAll();

        echo json_encode([
            'success' => true,
            'faqs' => $faqs
        ]);

    } elseif ($method === 'POST') {
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Admin access required']);
            exit();
        }

        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['category']) || !isset($data['question']) || !isset($data['answer'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
            exit();
        }

        $stmt = $db->prepare("
            INSERT INTO faqs (category, question, answer)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([
            $data['category'],
            $data['question'],
            $data['answer']
        ]);

        echo json_encode([
            'success' => true,
            'faq_id' => $db->lastInsertId(),
            'message' => 'FAQ created successfully'
        ]);

    } elseif ($method === 'PUT') {
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Admin access required']);
            exit();
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $faq_id = $data['id'] ?? null;

        if (!$faq_id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'FAQ ID is required']);
            exit();
        }

        $stmt = $db->prepare("
            UPDATE faqs 
            SET category = ?, question = ?, answer = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $data['category'],
            $data['question'],
            $data['answer'],
            $faq_id
        ]);

        echo json_encode([
            'success' => true,
            'message' => 'FAQ updated successfully'
        ]);

    } elseif ($method === 'DELETE') {
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Admin access required']);
            exit();
        }

        $faq_id = $_GET['id'] ?? null;

        if (!$faq_id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'FAQ ID is required']);
            exit();
        }

        $stmt = $db->prepare("DELETE FROM faqs WHERE id = ?");
        $stmt->execute([$faq_id]);

        echo json_encode([
            'success' => true,
            'message' => 'FAQ deleted successfully'
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

