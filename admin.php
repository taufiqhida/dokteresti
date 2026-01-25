<?php
session_start();

// Check if logging in
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    require_once __DIR__ . '/config/database.php';
    
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE username = ?");
        $stmt->execute([$username]);
        $admin = $stmt->fetch();
        
        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_name'] = $admin['name'];
            $_SESSION['admin_logged_in'] = true;
            header('Location: admin.php');
            exit;
        } else {
            $error = 'Username atau password salah';
        }
    } catch (Exception $e) {
        $error = 'Terjadi kesalahan server';
    }
}

// Check if logging out
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin.php');
    exit;
}

// Check if logged in
$isLoggedIn = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
$adminName = $_SESSION['admin_name'] ?? 'Admin';

// If logged in, get data
$results = [];
$stats = [];
$total = 0;

if ($isLoggedIn) {
    require_once __DIR__ . '/config/database.php';
    try {
        $pdo = getDBConnection();
        
        // Get filter parameters
        $type = $_GET['type'] ?? 'all';
        $search = $_GET['search'] ?? '';
        
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
        $sql = "SELECT * FROM screening_results $whereClause ORDER BY created_at DESC LIMIT 100";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll();
        
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
        
    } catch (Exception $e) {
        $error = 'Error loading data: ' . $e->getMessage();
    }
}

// Calculate totals for stats
$lowCount = 0;
$mediumCount = 0;
$highCount = 0;
foreach ($stats as $s) {
    $lowCount += (int)($s['low'] ?? 0);
    $mediumCount += (int)($s['medium'] ?? 0);
    $highCount += (int)($s['high'] ?? 0) + (int)($s['critical'] ?? 0);
}

// Handle delete
if ($isLoggedIn && isset($_GET['delete'])) {
    require_once __DIR__ . '/config/database.php';
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("DELETE FROM screening_results WHERE id = ?");
        $stmt->execute([$_GET['delete']]);
        header('Location: admin.php');
        exit;
    } catch (Exception $e) {
        $error = 'Error deleting';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Bugangan Mental Care</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --secondary: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --text: #1e293b;
            --text-light: #64748b;
            --bg: #f8fafc;
            --bg-white: #ffffff;
            --border: #e2e8f0;
            --radius: 12px;
            --shadow: 0 4px 6px -1px rgba(0,0,0,.1);
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: var(--bg); color: var(--text); min-height: 100vh; }
        
        /* Login */
        .login-container { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 2rem; }
        .login-card { background: var(--bg-white); padding: 3rem; border-radius: var(--radius); box-shadow: var(--shadow); width: 100%; max-width: 400px; }
        .login-logo { text-align: center; margin-bottom: 2rem; font-size: 3rem; }
        .login-card h1 { text-align: center; font-size: 1.5rem; margin-bottom: 0.5rem; }
        .login-card > p { text-align: center; color: var(--text-light); margin-bottom: 2rem; }
        .form-group { margin-bottom: 1.25rem; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 0.5rem; }
        .form-group input { width: 100%; padding: 0.875rem 1rem; border: 2px solid var(--border); border-radius: 8px; font-size: 1rem; }
        .form-group input:focus { outline: none; border-color: var(--primary); }
        .btn { width: 100%; padding: 1rem; background: var(--primary); color: white; border: none; border-radius: 8px; font-weight: 600; font-size: 1rem; cursor: pointer; }
        .btn:hover { background: var(--primary-dark); }
        .error-msg { background: #fef2f2; color: #dc2626; padding: 0.75rem 1rem; border-radius: 8px; margin-bottom: 1rem; }
        
        /* Header */
        .header { background: var(--bg-white); border-bottom: 1px solid var(--border); padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; }
        .header h1 { font-size: 1.25rem; display: flex; align-items: center; gap: 0.5rem; }
        .header-actions { display: flex; gap: 1rem; align-items: center; }
        .btn-logout { padding: 0.5rem 1rem; background: #fee2e2; color: #dc2626; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; text-decoration: none; }
        .btn-back { padding: 0.5rem 1rem; background: var(--bg); color: var(--text); border: none; border-radius: 6px; font-weight: 600; text-decoration: none; }
        
        .main { padding: 2rem; max-width: 1400px; margin: 0 auto; }
        
        /* Stats */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
        .stat-card { background: var(--bg-white); padding: 1.5rem; border-radius: var(--radius); box-shadow: var(--shadow); }
        .stat-value { font-size: 2rem; font-weight: 800; color: var(--primary); }
        .stat-label { color: var(--text-light); font-size: 0.9rem; margin-top: 0.25rem; }
        .stat-card.success .stat-value { color: var(--secondary); }
        .stat-card.warning .stat-value { color: var(--warning); }
        .stat-card.danger .stat-value { color: var(--danger); }
        
        /* Filters */
        .filters { background: var(--bg-white); padding: 1.5rem; border-radius: var(--radius); box-shadow: var(--shadow); margin-bottom: 2rem; display: flex; gap: 1rem; flex-wrap: wrap; align-items: center; }
        .filters select, .filters input { padding: 0.625rem 1rem; border: 2px solid var(--border); border-radius: 8px; font-size: 0.95rem; }
        .btn-filter { padding: 0.625rem 1.25rem; background: var(--primary); color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; }
        
        /* Table */
        .table-container { background: var(--bg-white); border-radius: var(--radius); box-shadow: var(--shadow); overflow: hidden; }
        .table-header { padding: 1rem 1.5rem; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; }
        .table-header h2 { font-size: 1.1rem; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 1rem 1.5rem; text-align: left; border-bottom: 1px solid var(--border); }
        th { background: var(--bg); font-weight: 600; font-size: 0.85rem; text-transform: uppercase; color: var(--text-light); }
        tr:hover { background: #f8fafc; }
        .badge { display: inline-block; padding: 0.25rem 0.75rem; border-radius: 50px; font-size: 0.8rem; font-weight: 600; }
        .badge-epds { background: #e0e7ff; color: #3730a3; }
        .badge-phq { background: #d1fae5; color: #065f46; }
        .badge-mmys-teen { background: #fef3c7; color: #92400e; }
        .badge-mmys-child { background: #fce7f3; color: #9d174d; }
        .badge-low { background: #d1fae5; color: #065f46; }
        .badge-medium { background: #fef3c7; color: #92400e; }
        .badge-high { background: #fee2e2; color: #991b1b; }
        .badge-critical { background: #991b1b; color: white; }
        .btn-delete { padding: 0.375rem 0.75rem; background: var(--danger); color: white; border: none; border-radius: 6px; font-size: 0.85rem; cursor: pointer; text-decoration: none; }
        .empty-state { text-align: center; padding: 3rem; color: var(--text-light); }
        
        @media (max-width: 768px) {
            .filters { flex-direction: column; }
            .filters select, .filters input { width: 100%; }
            .table-container { overflow-x: auto; }
            table { min-width: 600px; }
            .header { flex-direction: column; text-align: center; }
        }
    </style>
</head>
<body>
<?php if (!$isLoggedIn): ?>
    <!-- Login Page -->
    <div class="login-container">
        <div class="login-card">
            <div class="login-logo">🔐</div>
            <h1>Admin Login</h1>
            <p>Masuk untuk mengakses dashboard skrining</p>
            <?php if (isset($error)): ?>
                <div class="error-msg"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <form method="POST">
                <input type="hidden" name="action" value="login">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" required placeholder="Masukkan username">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required placeholder="Masukkan password">
                </div>
                <button type="submit" class="btn">Login</button>
            </form>
        </div>
    </div>
<?php else: ?>
    <!-- Dashboard -->
    <header class="header">
        <h1>🧠 Bugangan Mental Care - Dashboard Skrining</h1>
        <div class="header-actions">
            <span>👤 <?= htmlspecialchars($adminName) ?></span>
            <a href="index.php" class="btn-back">← Website</a>
            <a href="admin.php?logout=1" class="btn-logout">Logout</a>
        </div>
    </header>
    
    <main class="main">
        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?= $total ?></div>
                <div class="stat-label">Total Skrining</div>
            </div>
            <div class="stat-card success">
                <div class="stat-value"><?= $lowCount ?></div>
                <div class="stat-label">Risiko Rendah</div>
            </div>
            <div class="stat-card warning">
                <div class="stat-value"><?= $mediumCount ?></div>
                <div class="stat-label">Risiko Sedang</div>
            </div>
            <div class="stat-card danger">
                <div class="stat-value"><?= $highCount ?></div>
                <div class="stat-label">Risiko Tinggi</div>
            </div>
        </div>
        
        <!-- Filters -->
        <form class="filters" method="GET">
            <select name="type">
                <option value="all" <?= ($type ?? 'all') === 'all' ? 'selected' : '' ?>>Semua Jenis</option>
                <option value="EPDS" <?= ($type ?? '') === 'EPDS' ? 'selected' : '' ?>>EPDS</option>
                <option value="PHQ2-GAD2" <?= ($type ?? '') === 'PHQ2-GAD2' ? 'selected' : '' ?>>PHQ-2 & GAD-2</option>
                <option value="MMYS-TEEN" <?= ($type ?? '') === 'MMYS-TEEN' ? 'selected' : '' ?>>MMYS 10-18 Tahun</option>
                <option value="MMYS-CHILD" <?= ($type ?? '') === 'MMYS-CHILD' ? 'selected' : '' ?>>MMYS 7-9 Tahun</option>
            </select>
            <input type="text" name="search" placeholder="Cari nama atau HP..." value="<?= htmlspecialchars($search ?? '') ?>">
            <button type="submit" class="btn-filter">🔍 Filter</button>
        </form>
        
        <!-- Table -->
        <div class="table-container">
            <div class="table-header">
                <h2>Hasil Skrining</h2>
                <span><?= count($results) ?> data</span>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Nama</th>
                        <th>Jenis</th>
                        <th>Skor</th>
                        <th>Hasil</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($results)): ?>
                        <tr><td colspan="6" class="empty-state">📋 Tidak ada data skrining</td></tr>
                    <?php else: ?>
                        <?php foreach ($results as $r): ?>
                            <?php
                            $typeBadges = [
                                'EPDS' => 'badge-epds',
                                'PHQ2-GAD2' => 'badge-phq',
                                'MMYS-TEEN' => 'badge-mmys-teen',
                                'MMYS-CHILD' => 'badge-mmys-child'
                            ];
                            $resultBadges = [
                                'RENDAH' => 'badge-low',
                                'SEDANG' => 'badge-medium',
                                'TINGGI' => 'badge-high',
                                'KRITIS' => 'badge-critical'
                            ];
                            $typeClass = $typeBadges[$r['screening_type']] ?? '';
                            $resultClass = $resultBadges[$r['interpretation']] ?? '';
                            ?>
                            <tr>
                                <td><?= date('d/m/Y H:i', strtotime($r['created_at'])) ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($r['respondent_name']) ?></strong><br>
                                    <small><?= htmlspecialchars($r['respondent_phone'] ?? '-') ?></small>
                                </td>
                                <td><span class="badge <?= $typeClass ?>"><?= $r['screening_type'] ?></span></td>
                                <td><?= $r['total_score'] ?></td>
                                <td><span class="badge <?= $resultClass ?>"><?= $r['interpretation'] ?></span></td>
                                <td>
                                    <a href="admin.php?delete=<?= $r['id'] ?>" class="btn-delete" onclick="return confirm('Yakin hapus?')">Hapus</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
<?php endif; ?>
</body>
</html>