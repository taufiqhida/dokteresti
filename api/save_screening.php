<?php
/**
 * API untuk menyimpan hasil skrining ke database
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../config/database.php';

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate required fields
$required = ['screening_type', 'respondent_name', 'answers', 'total_score', 'interpretation'];
foreach ($required as $field) {
    if (empty($input[$field]) && $input[$field] !== 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => "Field '$field' is required"]);
        exit;
    }
}

// Validate screening type
if (!in_array($input['screening_type'], ['EPDS', 'PHQ2-GAD2'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid screening type']);
    exit;
}

try {
    $pdo = getDBConnection();
    
    $sql = "INSERT INTO screening_results 
            (screening_type, respondent_name, respondent_phone, respondent_age, answers, total_score, phq2_score, gad2_score, interpretation, is_critical) 
            VALUES 
            (:screening_type, :respondent_name, :respondent_phone, :respondent_age, :answers, :total_score, :phq2_score, :gad2_score, :interpretation, :is_critical)";
    
    $stmt = $pdo->prepare($sql);
    
    $stmt->execute([
        ':screening_type' => $input['screening_type'],
        ':respondent_name' => $input['respondent_name'],
        ':respondent_phone' => $input['respondent_phone'] ?? null,
        ':respondent_age' => $input['respondent_age'] ?? null,
        ':answers' => json_encode($input['answers']),
        ':total_score' => $input['total_score'],
        ':phq2_score' => $input['phq2_score'] ?? null,
        ':gad2_score' => $input['gad2_score'] ?? null,
        ':interpretation' => $input['interpretation'],
        ':is_critical' => $input['is_critical'] ?? 0
    ]);
    
    $insertId = $pdo->lastInsertId();
    
    echo json_encode([
        'success' => true,
        'message' => 'Hasil skrining berhasil disimpan',
        'id' => $insertId
    ]);
    
} catch (Exception $e) {
    error_log("Save screening error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Terjadi kesalahan saat menyimpan data']);
}
