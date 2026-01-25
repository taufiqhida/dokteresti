<?php
/**
 * API untuk mendapatkan hasil skrining (Admin Only)
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../config/database.php';

// Simple token verification
function verifyToken($pdo)
{
    $token = null;

    // Try multiple ways to get Authorization header
    if (function_exists('getallheaders')) {
        $headers = getallheaders();
        // Headers can be case-insensitive
        foreach ($headers as $key => $value) {
            if (strtolower($key) === 'authorization') {
                $token = str_replace('Bearer ', '', $value);
                break;
            }
        }
    }

    // Fallback to $_SERVER
    if (!$token && isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $token = str_replace('Bearer ', '', $_SERVER['HTTP_AUTHORIZATION']);
    }

    // Another fallback for Apache
    if (!$token && isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        $token = str_replace('Bearer ', '', $_SERVER['REDIRECT_HTTP_AUTHORIZATION']);
    }

    if (!$token) {
        return false;
    }

    $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE token = ? AND token_expires > NOW()");
    $stmt->execute([$token]);
    return $stmt->fetch();
}

try {
    $pdo = getDBConnection();

    // Verify admin token
    $admin = verifyToken($pdo);
    if (!$admin) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Get filter parameters
        $type = $_GET['type'] ?? null;
        $search = $_GET['search'] ?? null;
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
        $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;

        $where = [];
        $params = [];

        if ($type && $type !== 'all') {
            $where[] = "screening_type = ?";
            $params[] = $type;
        }

        if ($search) {
            $where[] = "(respondent_name LIKE ? OR respondent_phone LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        $whereClause = count($where) > 0 ? "WHERE " . implode(" AND ", $where) : "";

        // Get total count
        $countStmt = $pdo->prepare("SELECT COUNT(*) as total FROM screening_results $whereClause");
        $countStmt->execute($params);
        $total = $countStmt->fetch()['total'];

        // Get results
        $sql = "SELECT * FROM screening_results $whereClause ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll();

        // Decode JSON answers
        foreach ($results as &$result) {
            $result['answers'] = json_decode($result['answers'], true);
        }

        // Get stats
        $statsStmt = $pdo->query("
            SELECT 
                screening_type,
                COUNT(*) as count,
                SUM(CASE WHEN interpretation = 'RENDAH' THEN 1 ELSE 0 END) as low,
                SUM(CASE WHEN interpretation = 'SEDANG' THEN 1 ELSE 0 END) as medium,
                SUM(CASE WHEN interpretation = 'TINGGI' THEN 1 ELSE 0 END) as high,
                SUM(CASE WHEN interpretation = 'KRITIS' THEN 1 ELSE 0 END) as critical
            FROM screening_results 
            GROUP BY screening_type
        ");
        $stats = $statsStmt->fetchAll();

        echo json_encode([
            'success' => true,
            'data' => $results,
            'total' => $total,
            'stats' => $stats
        ]);

    } elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        $id = $_GET['id'] ?? null;

        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'ID required']);
            exit;
        }

        $stmt = $pdo->prepare("DELETE FROM screening_results WHERE id = ?");
        $stmt->execute([$id]);

        echo json_encode(['success' => true, 'message' => 'Data berhasil dihapus']);
    }

} catch (Exception $e) {
    error_log("Get results error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Terjadi kesalahan server']);
}
