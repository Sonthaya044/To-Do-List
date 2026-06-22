<<<<<<< HEAD
<?php
// toggle_status.php
session_start();
include "includes/config.php";

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($_SESSION['username']) || !isset($data['note_id']) || !isset($data['status'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

$note_id = $data['note_id'];
$is_completed = $data['status'] ? 1 : 0;
$username = $_SESSION['username'];

$stmt = $conn->prepare("UPDATE notes SET is_completed = ? WHERE id = ? AND username = ?");
$stmt->bind_param("iis", $is_completed, $note_id, $username);

if ($stmt->execute() && $stmt->affected_rows > 0) {
    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to update status.']);
}

$stmt->close();
$conn->close();
=======
<?php
// toggle_status.php
session_start();
require_once __DIR__ . "/includes/config.php";

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($_SESSION['username']) || !isset($data['note_id']) || !isset($data['status'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

$note_id = $data['note_id'];
$is_completed = $data['status'] ? 1 : 0;
$username = $_SESSION['username'];

$stmt = $conn->prepare("UPDATE notes SET is_completed = ? WHERE id = ? AND username = ?");
$stmt->bind_param("iis", $is_completed, $note_id, $username);

if ($stmt->execute() && $stmt->affected_rows > 0) {
    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to update status.']);
}

$stmt->close();
$conn->close();
>>>>>>> 9d61535 (Prepare Vercel PHP deploy: add vercel.json and fix config includes)
?>