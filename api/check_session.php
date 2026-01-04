<?php
header('Content-Type: application/json');
require_once '../database/connection.php';

session_start();

if (isset($_SESSION['user_id']) && isset($_SESSION['username'])) {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT id, username, role, email, full_name FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();

        if ($user) {
            echo json_encode([
                'success' => true,
                'user' => $user
            ]);
        } else {
            session_destroy();
            echo json_encode([
                'success' => false,
                'message' => 'User not found'
            ]);
        }
    } catch (Exception $e) {
        session_destroy();
        echo json_encode([
            'success' => false,
            'message' => 'Session error'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'No active session'
    ]);
}
?>

