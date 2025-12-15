<?php
session_start();
require_once 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['member_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$member_id = $_SESSION['member_id'];
$method = $_SERVER['REQUEST_METHOD'];
$mysqli = db_get_connection();

$input = file_get_contents('php://input');

switch ($method) {
    case 'GET':
        $stmt = $mysqli->prepare("SELECT theater_place_name, theater_x, theater_y FROM favorite_theater WHERE member_id = ?");
        $stmt->bind_param('i', $member_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $favorites = [];
        while ($row = $result->fetch_assoc()) {
            $favorites[] = $row;
        }
        
        echo json_encode($favorites);
        break;
        
    case 'POST':
        $data = json_decode($input, true);
        
        $place_name = $data['theater_place_name'] ?? '';
        $x = $data['theater_x'] ?? 0;
        $y = $data['theater_y'] ?? 0;
        
        if (!$place_name || !$x || !$y) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid parameters']);
            exit;
        }
        
        $stmt = $mysqli->prepare("INSERT IGNORE INTO favorite_theater (member_id, theater_place_name, theater_x, theater_y) VALUES (?, ?, ?, ?)");
        $stmt->bind_param('isdd', $member_id, $place_name, $x, $y);
        $stmt->execute();
        
        echo json_encode(['success' => true]);
        break;
        
    case 'DELETE':
        $data = json_decode($input, true);
        
        $place_name = $data['theater_place_name'] ?? '';
        
        if (!$place_name) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid parameters']);
            exit;
        }
        
        $stmt = $mysqli->prepare("DELETE FROM favorite_theater WHERE member_id = ? AND theater_place_name = ?");
        $stmt->bind_param('is', $member_id, $place_name);
        $stmt->execute();
        
        echo json_encode(['success' => true]);
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
}
?>
