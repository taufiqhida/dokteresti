<?php
/**
 * Script untuk reset password admin
 * Hapus file ini setelah digunakan!
 */

require_once __DIR__ . '/config/database.php';

$password = 'admin';
$hash = password_hash($password, PASSWORD_DEFAULT);

try {
    $pdo = getDBConnection();

    // Delete existing admin
    $pdo->exec("DELETE FROM admin_users WHERE username = 'admin'");

    // Create new admin with correct hash
    $stmt = $pdo->prepare("INSERT INTO admin_users (username, password, name) VALUES (?, ?, ?)");
    $stmt->execute(['admin', $hash, 'Administrator']);

    echo "<h2>✅ Password Admin Berhasil Direset!</h2>";
    echo "<p><strong>Username:</strong> admin</p>";
    echo "<p><strong>Password:</strong> admin</p>";
    echo "<p><a href='admin.php'>→ Klik untuk Login ke Admin</a></p>";
    echo "<br><p style='color:red;'><strong>PENTING:</strong> Hapus file reset_admin.php ini setelah login berhasil!</p>";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
