<?php
// api/meeting_status.php
session_start();
header('Content-Type: application/json');

require_once '../includes/db.php';

// Default response if something goes wrong
$response = ['status' => null, 'message' => 'No active session found.'];

// Check if the user is properly logged in with a session
if (!isset($_SESSION['user_id'])) {
    echo json_encode($response);
    exit;
}

try {
    $property_id = $_SESSION['user_id'];

    // Find the status of the meeting associated with the user's most recent session.
    // We join user_sessions with meetings to get the status directly.
    $stmt = $pdo->prepare(
        "SELECT m.status
         FROM meetings m
         JOIN user_sessions us ON m.id = us.meeting_id
         WHERE us.property_id = ?
         ORDER BY us.login_time DESC
         LIMIT 1"
    );
    $stmt->execute([$property_id]);
    $meeting_status = $stmt->fetchColumn();

    if ($meeting_status) {
        // If a status is found, return it.
        $response['status'] = $meeting_status;
        $response['message'] = 'Status retrieved successfully.';
    } else {
        // This case handles if a session exists but the linked meeting is gone.
        $response['message'] = 'Could not find a meeting associated with your session.';
    }

} catch (PDOException $e) {
    // Log the error internally instead of exposing it.
    error_log("Database error in meeting_status.php: " . $e->getMessage());
    $response['status'] = 'error';
    $response['message'] = 'A database error occurred while checking status.';
} catch (Exception $e) {
    error_log("General error in meeting_status.php: " . $e->getMessage());
    $response['status'] = 'error';
    $response['message'] = 'An unexpected error occurred.';
}

echo json_encode($response);
?>