<?php
session_start();

// ── Login handler ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    require_once __DIR__ . '/config/database.php';
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    try {
        $pdo  = getDBConnection();
        $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE username = ?");
        $stmt->execute([$username]);
        $admin = $stmt->fetch();
        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['admin_id']         = $admin['id'];
            $_SESSION['admin_name']       = $admin['name'];
            $_SESSION['admin_logged_in']  = true;
            header('Location: admin.php');
            exit;
        } else {
            $error = 'Username atau password salah';
        }
    } catch (Exception $e) {
        $error = 'Terjadi kesalahan server';
    }
}

// ── Logout ─────────────────────────────────────────────────────
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin.php');
    exit;
}

// ── Delete ─────────────────────────────────────────────────────
$isLoggedIn = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
$adminName  = $_SESSION['admin_name'] ?? 'Admin';

if ($isLoggedIn && isset($_GET['delete'])) {
    require_once __DIR__ . '/config/database.php';
    try {
        $pdo  = getDBConnection();
        $stmt = $pdo->prepare("DELETE FROM screening_results WHERE id = ?");
        $stmt->execute([$_GET['delete']]);
        header('Location: admin.php');
        exit;
    } catch (Exception $e) { $error = 'Error deleting'; }
}

// ── Data loading ───────────────────────────────────────────────
$results   = [];
$stats     = [];
$total     = 0;
$trendData = [];
$phoneFreq = [];

if ($isLoggedIn) {
    require_once __DIR__ . '/config/database.php';
    try {
        $pdo    = getDBConnection();
        $type   = $_GET['type']   ?? 'all';
        $search = $_GET['search'] ?? '';

        $where  = [];
        $params = [];

        if ($type && $type !== 'all') {
            $where[]  = "screening_type = ?";
            $params[] = $type;
        }
        if ($search) {
            $where[]  = "(respondent_name LIKE ? OR respondent_phone LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        $whereClause = count($where) > 0 ? "WHERE " . implode(" AND ", $where) : "";

        // Total count
        $countStmt = $pdo->prepare("SELECT COUNT(*) as total FROM screening_results $whereClause");
        $countStmt->execute($params);
        $total = $countStmt->fetch()['total'];

        // Results (latest 100)
        $stmt = $pdo->prepare("SELECT * FROM screening_results $whereClause ORDER BY created_at DESC LIMIT 100");
        $stmt->execute($params);
        $results = $stmt->fetchAll();

        // Stats per type
        $statsStmt = $pdo->query("
            SELECT
                screening_type,
                COUNT(*) as count,
                SUM(CASE WHEN interpretation='RENDAH' THEN 1 ELSE 0 END) as low,
                SUM(CASE WHEN interpretation='SEDANG' THEN 1 ELSE 0 END) as medium,
                SUM(CASE WHEN interpretation='TINGGI' THEN 1 ELSE 0 END) as high,
                SUM(CASE WHEN interpretation='KRITIS' THEN 1 ELSE 0 END) as critical
            FROM screening_results
            GROUP BY screening_type
        ");
        $stats = $statsStmt->fetchAll();

        // Trend: last 6 months
        $trendStmt = $pdo->query("
            SELECT DATE_FORMAT(created_at,'%b %Y') as month_label,
                   DATE_FORMAT(created_at,'%Y-%m') as month_key,
                   COUNT(*) as count
            FROM screening_results
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
            GROUP BY month_key, month_label
            ORDER BY month_key ASC
        ");
        $trendData = $trendStmt->fetchAll();

        // Phone frequency (for duplicate badge)
        $freqStmt = $pdo->query("
            SELECT respondent_phone, COUNT(*) as freq
            FROM screening_results
            WHERE respondent_phone IS NOT NULL AND respondent_phone != ''
            GROUP BY respondent_phone
            HAVING freq > 1
        ");
        foreach ($freqStmt->fetchAll() as $row) {
            $phoneFreq[$row['respondent_phone']] = (int)$row['freq'];
        }

    } catch (Exception $e) {
        $error = 'Error loading data: ' . $e->getMessage();
    }
}

// ── Aggregate counts ────────────────────────────────────────────
$lowCount = $mediumCount = $highCount = $criticalCount = 0;
foreach ($stats as $s) {
    $lowCount      += (int)($s['low']      ?? 0);
    $mediumCount   += (int)($s['medium']   ?? 0);
    $highCount     += (int)($s['high']     ?? 0);
    $criticalCount += (int)($s['critical'] ?? 0);
}
$pieData = [$lowCount, $mediumCount, $highCount, $criticalCount];

// ── WA helper ───────────────────────────────────────────────────
function buildWaLink(string $phone, string $name): string {
    $digits = preg_replace('/\D/', '', $phone);
    if (str_starts_with($digits, '0')) {
        $digits = '62' . substr($digits, 1);
    } elseif (!str_starts_with($digits, '62')) {
        $digits = '62' . $digits;
    }
    $msg = urlencode("Halo $name, kami dari Puskesmas Bugangan ingin menindaklanjuti hasil skrining kesehatan mental Anda. Apakah Anda bersedia untuk berkonsultasi lebih lanjut?");
    return "https://wa.me/{$digits}?text={$msg}";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard — Bugangan Mental Care</title>
    <meta name="description" content="Dashboard admin skrining kesehatan mental Puskesmas Bugangan">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --secondary: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --text: #1e293b;
            --text-light: #64748b;
            --bg: #f1f5f9;
            --bg-white: #ffffff;
            --border: #e2e8f0;
            --radius: 14px;
            --shadow: 0 4px 20px rgba(0,0,0,.07);
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: var(--bg); color: var(--text); min-height: 100vh; }

        /* ── Login ─────────────────────────────────────────────── */
        .login-bg {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #312e81 100%);
            padding: 2rem;
        }
        .login-card {
            background: rgba(255,255,255,.05);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,.12);
            padding: 3rem;
            border-radius: 20px;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 25px 50px rgba(0,0,0,.4);
        }
        .login-logo { text-align: center; font-size: 3.5rem; margin-bottom: 1.5rem; }
        .login-card h1 { text-align: center; font-size: 1.5rem; color: white; margin-bottom: 0.5rem; }
        .login-card > p { text-align: center; color: rgba(255,255,255,.6); margin-bottom: 2rem; font-size: 0.9rem; }
        .form-group { margin-bottom: 1.25rem; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 0.5rem; color: rgba(255,255,255,.8); font-size: 0.88rem; }
        .form-group input {
            width: 100%;
            padding: 0.875rem 1rem;
            background: rgba(255,255,255,.08);
            border: 1px solid rgba(255,255,255,.15);
            border-radius: 10px;
            font-size: 1rem;
            color: white;
            transition: border-color .2s, background .2s;
        }
        .form-group input::placeholder { color: rgba(255,255,255,.35); }
        .form-group input:focus { outline: none; border-color: var(--primary); background: rgba(255,255,255,.12); }
        .btn-login {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            letter-spacing: .5px;
            transition: opacity .2s, transform .1s;
        }
        .btn-login:hover { opacity: .9; transform: translateY(-1px); }
        .error-msg { background: rgba(239,68,68,.15); border: 1px solid rgba(239,68,68,.3); color: #fca5a5; padding: 0.75rem 1rem; border-radius: 10px; margin-bottom: 1rem; font-size: 0.88rem; }

        /* ── Dashboard Header ──────────────────────────────────── */
        .header {
            background: var(--bg-white);
            border-bottom: 1px solid var(--border);
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: stretch;
            gap: 1rem;
            flex-wrap: wrap;
            box-shadow: 0 1px 3px rgba(0,0,0,.06);
        }
        .header-brand {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1.1rem 0;
        }
        .header-brand-icon {
            width: 38px; height: 38px;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.1rem;
        }
        .header-brand h1 { font-size: 1.05rem; font-weight: 700; }
        .header-brand small { font-size: 0.72rem; color: var(--text-light); display: block; }
        .header-actions { display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap; padding: 0.75rem 0; }
        .btn-header {
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            transition: all .2s;
        }
        .btn-logout { background: #fee2e2; color: #dc2626; }
        .btn-logout:hover { background: #fecaca; }
        .btn-back { background: var(--bg); color: var(--text); }
        .btn-back:hover { background: var(--border); }
        .btn-pdf {
            background: linear-gradient(135deg, #059669, #10b981);
            color: white;
            padding: 0.5rem 1.1rem;
        }
        .btn-pdf:hover { opacity: .9; transform: translateY(-1px); }
        .admin-pill {
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 50px;
            padding: 0.35rem 0.85rem;
            font-size: 0.82rem;
            color: var(--text-light);
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        /* ── Tabs ──────────────────────────────────────────────── */
        .tabs-bar {
            background: var(--bg-white);
            border-bottom: 1px solid var(--border);
            display: flex;
            gap: 0;
            padding: 0 2rem;
        }
        .tab-btn {
            padding: 0.9rem 1.5rem;
            border: none;
            background: transparent;
            font-family: inherit;
            font-size: 0.88rem;
            font-weight: 600;
            color: var(--text-light);
            cursor: pointer;
            border-bottom: 3px solid transparent;
            transition: color .2s, border-color .2s;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }
        .tab-btn:hover { color: var(--primary); }
        .tab-btn.active { color: var(--primary); border-bottom-color: var(--primary); }
        .tab-content { display: none; }
        .tab-content.active { display: block; }

        /* ── Main area ─────────────────────────────────────────── */
        .main { padding: 2rem; max-width: 1400px; margin: 0 auto; }

        /* ── Stats cards ───────────────────────────────────────── */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 1.25rem; margin-bottom: 2rem; }
        .stat-card {
            background: var(--bg-white);
            padding: 1.4rem;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            border-top: 4px solid transparent;
            transition: transform .2s, box-shadow .2s;
        }
        .stat-card:hover { transform: translateY(-2px); box-shadow: 0 8px 28px rgba(0,0,0,.1); }
        .stat-card.default { border-top-color: var(--primary); }
        .stat-card.success  { border-top-color: var(--secondary); }
        .stat-card.warning  { border-top-color: var(--warning); }
        .stat-card.danger   { border-top-color: var(--danger); }
        .stat-card.critical { border-top-color: #991b1b; }
        .stat-icon { font-size: 1.5rem; margin-bottom: 0.75rem; }
        .stat-value { font-size: 2rem; font-weight: 800; line-height: 1; }
        .stat-card.default .stat-value  { color: var(--primary); }
        .stat-card.success .stat-value  { color: var(--secondary); }
        .stat-card.warning .stat-value  { color: var(--warning); }
        .stat-card.danger .stat-value   { color: var(--danger); }
        .stat-card.critical .stat-value { color: #991b1b; }
        .stat-label { color: var(--text-light); font-size: 0.82rem; margin-top: 0.3rem; font-weight: 500; }

        /* ── PDF Controls ──────────────────────────────────────── */
        .pdf-bar {
            background: var(--bg-white);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 1.25rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
            margin-bottom: 1.75rem;
            box-shadow: var(--shadow);
        }
        .pdf-bar label { font-size: 0.85rem; font-weight: 600; color: var(--text-light); white-space: nowrap; }
        .pdf-bar input[type="date"],
        .pdf-bar select {
            padding: 0.55rem 0.875rem;
            border: 1.5px solid var(--border);
            border-radius: 8px;
            font-size: 0.88rem;
            font-family: inherit;
            color: var(--text);
            background: white;
            transition: border-color .2s;
        }
        .pdf-bar input[type="date"]:focus,
        .pdf-bar select:focus { outline: none; border-color: var(--primary); }
        .pdf-sep { color: var(--text-light); font-size: 0.85rem; }
        .btn-export-pdf {
            background: linear-gradient(135deg, #059669, #10b981);
            color: white;
            border: none;
            padding: 0.6rem 1.4rem;
            border-radius: 8px;
            font-weight: 700;
            font-size: 0.88rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.45rem;
            margin-left: auto;
            transition: opacity .2s, transform .1s;
            text-decoration: none;
        }
        .btn-export-pdf:hover { opacity: .9; transform: translateY(-1px); }

        /* ── Filters ───────────────────────────────────────────── */
        .filters {
            background: var(--bg-white);
            padding: 1.25rem 1.5rem;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            margin-bottom: 1.75rem;
            display: flex;
            gap: 0.875rem;
            flex-wrap: wrap;
            align-items: center;
        }
        .filters select, .filters input {
            padding: 0.6rem 1rem;
            border: 1.5px solid var(--border);
            border-radius: 8px;
            font-size: 0.88rem;
            font-family: inherit;
            color: var(--text);
            transition: border-color .2s;
        }
        .filters select:focus, .filters input:focus { outline: none; border-color: var(--primary); }
        .filters input { flex: 1; min-width: 200px; }
        .btn-filter {
            padding: 0.6rem 1.25rem;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.88rem;
            cursor: pointer;
            transition: background .2s;
        }
        .btn-filter:hover { background: var(--primary-dark); }

        /* ── Table ─────────────────────────────────────────────── */
        .table-container {
            background: var(--bg-white);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            overflow: hidden;
        }
        .table-header {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .table-header h2 { font-size: 1rem; font-weight: 700; }
        .table-header span { font-size: 0.82rem; color: var(--text-light); }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 0.875rem 1.25rem; text-align: left; border-bottom: 1px solid var(--border); }
        th {
            background: var(--bg);
            font-weight: 600;
            font-size: 0.75rem;
            text-transform: uppercase;
            color: var(--text-light);
            letter-spacing: .5px;
        }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: #f8fafc; transition: background .15s; }
        .badge { display: inline-block; padding: 0.25rem 0.7rem; border-radius: 50px; font-size: 0.75rem; font-weight: 700; }
        .badge-epds       { background: #e0e7ff; color: #3730a3; }
        .badge-phq        { background: #d1fae5; color: #065f46; }
        .badge-mmys-teen  { background: #fef3c7; color: #92400e; }
        .badge-mmys-child { background: #fce7f3; color: #9d174d; }
        .badge-low        { background: #d1fae5; color: #065f46; }
        .badge-medium     { background: #fef3c7; color: #92400e; }
        .badge-high       { background: #fee2e2; color: #991b1b; }
        .badge-critical   { background: #991b1b; color: white; }

        /* Duplicate badge */
        .badge-dup {
            background: linear-gradient(135deg, #e0e7ff, #ede9fe);
            color: #4338ca;
            font-size: 0.68rem;
            padding: 0.15rem 0.5rem;
            border-radius: 50px;
            font-weight: 700;
            margin-left: 0.35rem;
            vertical-align: middle;
            white-space: nowrap;
        }

        /* Patient name link */
        .patient-link {
            color: var(--text);
            font-weight: 600;
            text-decoration: none;
            transition: color .15s;
        }
        .patient-link:hover { color: var(--primary); text-decoration: underline; }

        /* WA link */
        .wa-link {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            color: #16a34a;
            font-size: 0.8rem;
            font-weight: 600;
            text-decoration: none;
            transition: color .15s;
        }
        .wa-link:hover { color: #15803d; }
        .wa-icon { width: 14px; height: 14px; fill: currentColor; }

        .btn-delete {
            padding: 0.35rem 0.75rem;
            background: #fee2e2;
            color: #dc2626;
            border: none;
            border-radius: 6px;
            font-size: 0.78rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: background .2s;
        }
        .btn-delete:hover { background: #fecaca; }
        .empty-state { text-align: center; padding: 4rem; color: var(--text-light); }

        /* ── Analytics Tab ─────────────────────────────────────── */
        .analytics-grid {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 1.5rem;
            margin-bottom: 1.75rem;
        }
        .chart-card {
            background: var(--bg-white);
            border-radius: var(--radius);
            padding: 1.5rem;
            box-shadow: var(--shadow);
        }
        .chart-card h3 {
            font-size: 0.82rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--text-light);
            margin-bottom: 1.25rem;
            font-weight: 600;
        }
        .chart-wrap { position: relative; }
        .chart-wrap.pie  { height: 280px; }
        .chart-wrap.bar  { height: 280px; }

        .analytics-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 1.25rem;
            margin-bottom: 1.75rem;
        }
        .summary-card {
            background: var(--bg-white);
            border-radius: var(--radius);
            padding: 1.25rem;
            box-shadow: var(--shadow);
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .summary-icon {
            width: 46px; height: 46px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.4rem;
            flex-shrink: 0;
        }
        .summary-icon.purple { background: #ede9fe; }
        .summary-icon.green  { background: #d1fae5; }
        .summary-icon.amber  { background: #fef3c7; }
        .summary-icon.red    { background: #fee2e2; }
        .summary-info h4 { font-size: 1.3rem; font-weight: 800; }
        .summary-info p  { font-size: 0.75rem; color: var(--text-light); margin-top: 0.1rem; }

        /* ── Responsive ─────────────────────────────────────────── */
        @media (max-width: 900px) {
            .analytics-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 768px) {
            .main { padding: 1rem; }
            .filters { flex-direction: column; }
            .filters select, .filters input { width: 100%; }
            .table-container { overflow-x: auto; }
            table { min-width: 650px; }
            .header { flex-direction: column; padding: 1rem; }
            .tabs-bar { padding: 0 1rem; overflow-x: auto; }
            .pdf-bar { flex-direction: column; align-items: flex-start; }
            .btn-export-pdf { margin-left: 0; width: 100%; justify-content: center; }
        }
    </style>
</head>
<body>

<?php if (!$isLoggedIn): ?>
<!-- ═══════════════════ LOGIN ═══════════════════ -->
<div class="login-bg">
    <div class="login-card">
        <div class="login-logo">🧠</div>
        <h1>Admin Login</h1>
        <p>Bugangan Mental Care — Puskesmas Bugangan</p>
        <?php if (isset($error)): ?>
            <div class="error-msg">⚠️ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST">
            <input type="hidden" name="action" value="login">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required placeholder="Masukkan username">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required placeholder="Masukkan password">
            </div>
            <button type="submit" class="btn-login">🔐 Masuk</button>
        </form>
    </div>
</div>

<?php else: ?>
<!-- ═══════════════════ DASHBOARD ═══════════════════ -->
<header class="header">
    <div class="header-brand">
        <div class="header-brand-icon">🧠</div>
        <div>
            <h1>Bugangan Mental Care</h1>
            <small>Dashboard Admin — Puskesmas Bugangan</small>
        </div>
    </div>
    <div class="header-actions">
        <div class="admin-pill">👤 <?= htmlspecialchars($adminName) ?></div>
        <a href="index.php" class="btn-header btn-back">← Website</a>
        <a href="admin.php?logout=1" class="btn-header btn-logout">Logout</a>
    </div>
</header>

<!-- Tab Navigation -->
<nav class="tabs-bar">
    <button class="tab-btn active" id="tab-data-btn" onclick="switchTab('data')">
        📋 Data Skrining
    </button>
    <button class="tab-btn" id="tab-analisis-btn" onclick="switchTab('analisis')">
        📊 Analisis & Laporan
    </button>
</nav>

<!-- ═══ TAB: DATA SKRINING ═══ -->
<div class="tab-content active" id="tab-data">
<main class="main">

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card default">
            <div class="stat-icon">📋</div>
            <div class="stat-value"><?= $total ?></div>
            <div class="stat-label">Total Skrining</div>
        </div>
        <div class="stat-card success">
            <div class="stat-icon">✅</div>
            <div class="stat-value"><?= $lowCount ?></div>
            <div class="stat-label">Risiko Rendah</div>
        </div>
        <div class="stat-card warning">
            <div class="stat-icon">⚠️</div>
            <div class="stat-value"><?= $mediumCount ?></div>
            <div class="stat-label">Risiko Sedang</div>
        </div>
        <div class="stat-card danger">
            <div class="stat-icon">🚨</div>
            <div class="stat-value"><?= $highCount ?></div>
            <div class="stat-label">Risiko Tinggi</div>
        </div>
        <div class="stat-card critical">
            <div class="stat-icon">🆘</div>
            <div class="stat-value"><?= $criticalCount ?></div>
            <div class="stat-label">Kritis</div>
        </div>
    </div>

    <!-- PDF Export Bar -->
    <div class="pdf-bar" id="pdf-bar">
        <label>📄 Ekspor PDF:</label>
        <label for="pdf-from">Dari</label>
        <input type="date" id="pdf-from" value="<?= date('Y-m-01') ?>">
        <span class="pdf-sep">s.d.</span>
        <label for="pdf-to">Sampai</label>
        <input type="date" id="pdf-to" value="<?= date('Y-m-d') ?>">
        <select id="pdf-type">
            <option value="all">Semua Jenis</option>
            <option value="EPDS">EPDS</option>
            <option value="PHQ2-GAD2">PHQ-2 &amp; GAD-2</option>
            <option value="MMYS-TEEN">MMYS 10-18 Tahun</option>
            <option value="MMYS-CHILD">MMYS 7-9 Tahun</option>
        </select>
        <a href="#" class="btn-export-pdf" id="btn-export-pdf" onclick="openPdf(event)">
            🖨️ Cetak PDF
        </a>
    </div>

    <!-- Filters -->
    <form class="filters" method="GET">
        <select name="type" id="filter-type">
            <option value="all"       <?= ($type ?? 'all') === 'all'       ? 'selected' : '' ?>>Semua Jenis</option>
            <option value="EPDS"      <?= ($type ?? '') === 'EPDS'         ? 'selected' : '' ?>>EPDS</option>
            <option value="PHQ2-GAD2" <?= ($type ?? '') === 'PHQ2-GAD2'    ? 'selected' : '' ?>>PHQ-2 &amp; GAD-2</option>
            <option value="MMYS-TEEN" <?= ($type ?? '') === 'MMYS-TEEN'    ? 'selected' : '' ?>>MMYS 10-18 Tahun</option>
            <option value="MMYS-CHILD"<?= ($type ?? '') === 'MMYS-CHILD'   ? 'selected' : '' ?>>MMYS 7-9 Tahun</option>
        </select>
        <input type="text" name="search" id="filter-search" placeholder="🔍 Cari nama atau nomor HP..." value="<?= htmlspecialchars($search ?? '') ?>">
        <button type="submit" class="btn-filter">Filter</button>
    </form>

    <!-- Table -->
    <div class="table-container">
        <div class="table-header">
            <h2>Hasil Skrining</h2>
            <span><?= count($results) ?> data ditampilkan<?= $total > 100 ? " dari $total total" : '' ?></span>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Pasien</th>
                    <th>Kontak</th>
                    <th>Instrumen</th>
                    <th>Skor</th>
                    <th>Hasil</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($results)): ?>
                    <tr><td colspan="7" class="empty-state">📋 Tidak ada data skrining</td></tr>
                <?php else: ?>
                    <?php
                    $typeBadges   = ['EPDS'=>'badge-epds','PHQ2-GAD2'=>'badge-phq','MMYS-TEEN'=>'badge-mmys-teen','MMYS-CHILD'=>'badge-mmys-child'];
                    $resultBadges = ['RENDAH'=>'badge-low','SEDANG'=>'badge-medium','TINGGI'=>'badge-high','KRITIS'=>'badge-critical'];
                    foreach ($results as $r):
                        $typeClass   = $typeBadges[$r['screening_type']] ?? '';
                        $resultClass = $resultBadges[$r['interpretation']] ?? '';
                        $phone       = $r['respondent_phone'] ?? '';
                        $name        = $r['respondent_name'];
                        $freq        = $phone ? ($phoneFreq[$phone] ?? 1) : 1;
                    ?>
                    <tr>
                        <td>
                            <span style="font-size:.85rem"><?= date('d/m/Y', strtotime($r['created_at'])) ?></span><br>
                            <small style="color:var(--text-light)"><?= date('H:i', strtotime($r['created_at'])) ?></small>
                        </td>
                        <td>
                            <?php if ($phone): ?>
                                <a href="patient_history.php?phone=<?= urlencode($phone) ?>" class="patient-link">
                                    <?= htmlspecialchars($name) ?>
                                </a>
                            <?php else: ?>
                                <strong><?= htmlspecialchars($name) ?></strong>
                            <?php endif; ?>
                            <?php if ($freq > 1): ?>
                                <span class="badge-dup">🔄 <?= $freq ?>x</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($phone): ?>
                                <a href="<?= htmlspecialchars(buildWaLink($phone, $name)) ?>" target="_blank" class="wa-link">
                                    <svg class="wa-icon" viewBox="0 0 24 24">
                                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                                    </svg>
                                    <?= htmlspecialchars($phone) ?>
                                </a>
                            <?php else: ?>
                                <span style="color:var(--text-light);font-size:.82rem">—</span>
                            <?php endif; ?>
                        </td>
                        <td><span class="badge <?= $typeClass ?>"><?= $r['screening_type'] ?></span></td>
                        <td><strong><?= $r['total_score'] ?></strong></td>
                        <td><span class="badge <?= $resultClass ?>"><?= $r['interpretation'] ?></span></td>
                        <td>
                            <a href="admin.php?delete=<?= $r['id'] ?>" class="btn-delete" onclick="return confirm('Yakin ingin menghapus data ini?')">Hapus</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>
</div><!-- /tab-data -->

<!-- ═══ TAB: ANALISIS ═══ -->
<div class="tab-content" id="tab-analisis">
<main class="main">

    <!-- Summary analytics cards -->
    <div class="analytics-summary">
        <div class="summary-card">
            <div class="summary-icon purple">📋</div>
            <div class="summary-info">
                <h4><?= $total ?></h4>
                <p>Total Skrining</p>
            </div>
        </div>
        <div class="summary-card">
            <div class="summary-icon green">👥</div>
            <div class="summary-info">
                <h4><?= $total - count($phoneFreq) ?></h4>
                <p>Pasien Unik (est.)</p>
            </div>
        </div>
        <div class="summary-card">
            <div class="summary-icon amber">⚠️</div>
            <div class="summary-info">
                <h4><?= $mediumCount + $highCount + $criticalCount ?></h4>
                <p>Perlu Perhatian</p>
            </div>
        </div>
        <div class="summary-card">
            <div class="summary-icon red">🔄</div>
            <div class="summary-info">
                <h4><?= count($phoneFreq) ?></h4>
                <p>Pasien Multi-Skrining</p>
            </div>
        </div>
    </div>

    <!-- Charts -->
    <div class="analytics-grid">
        <!-- Pie Chart -->
        <div class="chart-card">
            <h3>🥧 Distribusi Risiko</h3>
            <div class="chart-wrap pie">
                <canvas id="pieChart"></canvas>
            </div>
        </div>

        <!-- Bar Chart -->
        <div class="chart-card">
            <h3>📈 Tren Skrining 6 Bulan Terakhir</h3>
            <div class="chart-wrap bar">
                <canvas id="barChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Per-type breakdown -->
    <div class="chart-card">
        <h3>📊 Perbandingan Antar Instrumen Skrining</h3>
        <div class="chart-wrap bar" style="height:220px; margin-top:.5rem">
            <canvas id="typeChart"></canvas>
        </div>
    </div>

</main>
</div><!-- /tab-analisis -->

<?php endif; ?>

<script>
// ── Tab switching ──────────────────────────────────
function switchTab(tab) {
    document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
    document.getElementById('tab-' + tab).classList.add('active');
    document.getElementById('tab-' + tab + '-btn').classList.add('active');
    if (tab === 'analisis') initCharts();
}

// ── PDF Export ─────────────────────────────────────
function openPdf(e) {
    e.preventDefault();
    const from = document.getElementById('pdf-from').value;
    const to   = document.getElementById('pdf-to').value;
    const type = document.getElementById('pdf-type').value;
    if (!from || !to) { alert('Pilih rentang tanggal terlebih dahulu.'); return; }
    window.open(`export_pdf.php?date_from=${from}&date_to=${to}&type=${type}`, '_blank');
}

// ── Charts ─────────────────────────────────────────
let chartsInitialized = false;

function initCharts() {
    if (chartsInitialized) return;
    chartsInitialized = true;

    // Pie chart data from PHP
    const pieLabels = ['Rendah', 'Sedang', 'Tinggi', 'Kritis'];
    const pieValues = <?= json_encode($pieData) ?>;
    const pieColors = ['#10b981','#f59e0b','#ef4444','#991b1b'];
    const pieBorder = ['#059669','#d97706','#dc2626','#7f1d1d'];

    new Chart(document.getElementById('pieChart'), {
        type: 'doughnut',
        data: {
            labels: pieLabels,
            datasets: [{
                data: pieValues,
                backgroundColor: pieColors,
                borderColor: pieBorder,
                borderWidth: 2,
                hoverOffset: 8,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { padding: 16, font: { size: 12, weight: '600' }, usePointStyle: true, pointStyleWidth: 10 }
                },
                tooltip: {
                    callbacks: {
                        label: ctx => {
                            const sum = ctx.dataset.data.reduce((a,b) => a+b, 0);
                            const pct = sum > 0 ? Math.round(ctx.parsed / sum * 100) : 0;
                            return ` ${ctx.label}: ${ctx.parsed} (${pct}%)`;
                        }
                    }
                }
            },
            cutout: '60%',
        }
    });

    // Bar chart — monthly trend
    const trendRaw = <?= json_encode($trendData) ?>;
    const trendLabels = trendRaw.map(r => r.month_label);
    const trendCounts = trendRaw.map(r => parseInt(r.count));

    const barGrad = document.getElementById('barChart').getContext('2d').createLinearGradient(0, 0, 0, 280);
    barGrad.addColorStop(0, 'rgba(99,102,241,0.85)');
    barGrad.addColorStop(1, 'rgba(99,102,241,0.25)');

    new Chart(document.getElementById('barChart'), {
        type: 'bar',
        data: {
            labels: trendLabels.length ? trendLabels : ['Belum ada data'],
            datasets: [{
                label: 'Jumlah Skrining',
                data: trendCounts.length ? trendCounts : [0],
                backgroundColor: barGrad,
                borderColor: '#6366f1',
                borderWidth: 0,
                borderRadius: 8,
                borderSkipped: false,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: { label: ctx => ` ${ctx.parsed.y} skrining` } }
            },
            scales: {
                x: { grid: { display: false }, ticks: { font: { size: 11 }, color: '#64748b' } },
                y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,.05)' }, ticks: { font: { size: 11 }, color: '#64748b', stepSize: 1 } }
            }
        }
    });

    // Type breakdown chart
    const statsRaw = <?= json_encode($stats) ?>;
    const typeLabels = statsRaw.map(s => s.screening_type);
    const typeLow    = statsRaw.map(s => parseInt(s.low    || 0));
    const typeMed    = statsRaw.map(s => parseInt(s.medium || 0));
    const typeHigh   = statsRaw.map(s => parseInt(s.high   || 0));
    const typeCrit   = statsRaw.map(s => parseInt(s.critical|| 0));

    new Chart(document.getElementById('typeChart'), {
        type: 'bar',
        data: {
            labels: typeLabels.length ? typeLabels : ['Belum ada data'],
            datasets: [
                { label: 'Rendah',  data: typeLow,  backgroundColor: '#10b981', borderRadius: 4, borderSkipped: false },
                { label: 'Sedang',  data: typeMed,  backgroundColor: '#f59e0b', borderRadius: 4, borderSkipped: false },
                { label: 'Tinggi',  data: typeHigh, backgroundColor: '#ef4444', borderRadius: 4, borderSkipped: false },
                { label: 'Kritis',  data: typeCrit, backgroundColor: '#991b1b', borderRadius: 4, borderSkipped: false },
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { padding: 16, font: { size: 11, weight: '600' }, usePointStyle: true } },
            },
            scales: {
                x: { stacked: false, grid: { display: false }, ticks: { font: { size: 11 }, color: '#64748b' } },
                y: { stacked: false, beginAtZero: true, grid: { color: 'rgba(0,0,0,.05)' }, ticks: { font: { size: 11 }, color: '#64748b', stepSize: 1 } }
            }
        }
    });
}
</script>
</body>
</html>