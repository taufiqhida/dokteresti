<?php
/**
 * API untuk Admin Login
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (empty($input['username']) || empty($input['password'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Username dan password harus diisi']);
    exit;
}

try {
    $pdo = getDBConnection();

    $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE username = ?");
    $stmt->execute([$input['username']]);
    $admin = $stmt->fetch();

    if (!$admin) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Username atau password salah']);
        exit;
    }

    // Verify password
    if (!password_verify($input['password'], $admin['password'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Username atau password salah']);
        exit;
    }

    // Generate simple token
    $token = bin2hex(random_bytes(32));

    // Store token in session or update database (simplified version)
    $updateStmt = $pdo->prepare("UPDATE admin_users SET token = ?, token_expires = DATE_ADD(NOW(), INTERVAL 24 HOUR) WHERE id = ?");
    $updateStmt->execute([$token, $admin['id']]);

    echo json_encode([
        'success' => true,
        'message' => 'Login berhasil',
        'token' => $token,
        'admin' => [
            'id' => $admin['id'],
            'username' => $admin['username'],
            'name' => $admin['name']
        ]
    ]);

} catch (Exception $e) {
    error_log("Admin login error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Terjadi kesalahan server']);
}
