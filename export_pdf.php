<?php
session_start();

// Guard: must be logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin.php');
    exit;
}

require_once __DIR__ . '/config/database.php';

$dateFrom = $_GET['date_from'] ?? date('Y-m-01');
$dateTo   = $_GET['date_to']   ?? date('Y-m-d');
$type     = $_GET['type']      ?? 'all';
$adminName = $_SESSION['admin_name'] ?? 'Admin';

try {
    $pdo = getDBConnection();

    $where  = ["DATE(created_at) BETWEEN ? AND ?"];
    $params = [$dateFrom, $dateTo];

    if ($type && $type !== 'all') {
        $where[] = "screening_type = ?";
        $params[] = $type;
    }

    $whereClause = "WHERE " . implode(" AND ", $where);

    $sql  = "SELECT * FROM screening_results $whereClause ORDER BY created_at ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll();

    // Summary counts
    $low = $medium = $high = $critical = 0;
    foreach ($results as $r) {
        match ($r['interpretation']) {
            'RENDAH'  => $low++,
            'SEDANG'  => $medium++,
            'TINGGI'  => $high++,
            'KRITIS'  => $critical++,
            default   => null,
        };
    }
} catch (Exception $e) {
    die('Error: ' . $e->getMessage());
}

$typeLabel = match($type) {
    'EPDS'       => 'EPDS',
    'PHQ2-GAD2'  => 'PHQ-2 & GAD-2',
    'MMYS-TEEN'  => 'MMYS 10–18 Tahun',
    'MMYS-CHILD' => 'MMYS 7–9 Tahun',
    default      => 'Semua Jenis',
};

$dateFromFormatted = date('d F Y', strtotime($dateFrom));
$dateToFormatted   = date('d F Y', strtotime($dateTo));
$printTime         = date('d F Y, H:i') . ' WIB';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Skrining — Puskesmas Bugangan</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');

        :root {
            --primary: #6366f1;
            --secondary: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --text: #1e293b;
            --text-light: #64748b;
            --border: #e2e8f0;
            --bg: #f8fafc;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', Arial, sans-serif;
            background: var(--bg);
            color: var(--text);
            font-size: 13px;
        }

        /* ── Screen-only toolbar ── */
        .toolbar {
            background: #1e293b;
            color: white;
            padding: 0.875rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
        }
        .toolbar span { font-size: 0.9rem; opacity: .8; }
        .toolbar-actions { display: flex; gap: 0.75rem; }
        .btn-print {
            background: #6366f1;
            color: white;
            border: none;
            padding: 0.6rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            font-size: 0.9rem;
            transition: background .2s;
        }
        .btn-print:hover { background: #4f46e5; }
        .btn-back-link {
            background: rgba(255,255,255,.15);
            color: white;
            border: none;
            padding: 0.6rem 1.25rem;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            font-size: 0.9rem;
            text-decoration: none;
            transition: background .2s;
        }
        .btn-back-link:hover { background: rgba(255,255,255,.25); }

        /* ── Paper ── */
        .paper {
            max-width: 900px;
            margin: 2rem auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 24px rgba(0,0,0,.1);
            overflow: hidden;
        }

        /* ── Official Header ── */
        .doc-header {
            background: linear-gradient(135deg, #1e3a5f 0%, #2563eb 60%, #6366f1 100%);
            color: white;
            padding: 2rem 2.5rem 1.5rem;
        }
        .header-top {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            margin-bottom: 1.25rem;
            padding-bottom: 1.25rem;
            border-bottom: 1px solid rgba(255,255,255,.25);
        }
        .header-logo { font-size: 3.5rem; line-height: 1; }
        .header-identity h1 {
            font-size: 1.25rem;
            font-weight: 800;
            letter-spacing: .5px;
            margin-bottom: 0.2rem;
        }
        .header-identity p { font-size: 0.82rem; opacity: .85; line-height: 1.5; }
        .header-body {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            flex-wrap: wrap;
            gap: 0.75rem;
        }
        .doc-title h2 { font-size: 1.1rem; font-weight: 700; margin-bottom: 0.25rem; }
        .doc-title p  { font-size: 0.82rem; opacity: .85; }
        .doc-meta { text-align: right; font-size: 0.78rem; opacity: .8; line-height: 1.8; }

        /* ── Summary cards ── */
        .summary-section {
            padding: 1.5rem 2.5rem;
            background: #f8fafc;
            border-bottom: 1px solid var(--border);
        }
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 1rem;
        }
        .summary-card {
            background: white;
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 1rem;
            text-align: center;
        }
        .summary-card .num {
            font-size: 1.75rem;
            font-weight: 800;
            line-height: 1;
            margin-bottom: 0.3rem;
        }
        .summary-card .lbl { font-size: 0.72rem; color: var(--text-light); font-weight: 500; }
        .num-total   { color: #6366f1; }
        .num-low     { color: #10b981; }
        .num-medium  { color: #f59e0b; }
        .num-high    { color: #ef4444; }
        .num-critical{ color: #991b1b; }

        /* ── Table ── */
        .table-section { padding: 1.5rem 2.5rem 2rem; }
        .table-section h3 {
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--text-light);
            margin-bottom: 1rem;
            font-weight: 600;
        }
        table { width: 100%; border-collapse: collapse; }
        th {
            background: #1e3a5f;
            color: white;
            padding: 0.625rem 0.875rem;
            text-align: left;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .5px;
        }
        th:first-child  { border-radius: 0; }
        td {
            padding: 0.625rem 0.875rem;
            border-bottom: 1px solid var(--border);
            vertical-align: top;
            font-size: 0.82rem;
        }
        tr:nth-child(even) td { background: #f8fafc; }
        .badge {
            display: inline-block;
            padding: 0.2rem 0.6rem;
            border-radius: 50px;
            font-size: 0.72rem;
            font-weight: 700;
        }
        .badge-epds      { background: #e0e7ff; color: #3730a3; }
        .badge-phq       { background: #d1fae5; color: #065f46; }
        .badge-mmys-teen { background: #fef3c7; color: #92400e; }
        .badge-mmys-child{ background: #fce7f3; color: #9d174d; }
        .badge-low       { background: #d1fae5; color: #065f46; }
        .badge-medium    { background: #fef3c7; color: #92400e; }
        .badge-high      { background: #fee2e2; color: #991b1b; }
        .badge-critical  { background: #991b1b; color: white; }

        .empty-state { text-align: center; padding: 3rem; color: var(--text-light); font-size: 0.9rem; }

        /* ── Footer ── */
        .doc-footer {
            padding: 1.5rem 2.5rem;
            border-top: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            background: #f8fafc;
        }
        .footer-left { font-size: 0.78rem; color: var(--text-light); line-height: 1.8; }
        .signature-block { text-align: center; font-size: 0.8rem; }
        .signature-block .sig-title { color: var(--text-light); margin-bottom: 3.5rem; }
        .signature-block .sig-name { font-weight: 700; border-top: 1px solid var(--text); padding-top: 0.4rem; }

        /* ── Print styles ── */
        @media print {
            body { background: white; }
            .toolbar { display: none !important; }
            .paper {
                max-width: 100%;
                margin: 0;
                border-radius: 0;
                box-shadow: none;
            }
            tr { page-break-inside: avoid; }
            thead { display: table-header-group; }
            @page {
                margin: 1.5cm 1.5cm 1.5cm 1.5cm;
                size: A4;
            }
        }
    </style>
</head>
<body>

<!-- Screen-only toolbar -->
<div class="toolbar">
    <span>📄 Preview Laporan — <?= htmlspecialchars($typeLabel) ?> | <?= $dateFromFormatted ?> s.d. <?= $dateToFormatted ?></span>
    <div class="toolbar-actions">
        <a href="admin.php" class="btn-back-link">← Kembali</a>
        <button class="btn-print" onclick="window.print()">🖨️ Cetak / Simpan PDF</button>
    </div>
</div>

<!-- Paper document -->
<div class="paper">

    <!-- Official header -->
    <div class="doc-header">
        <div class="header-top">
            <div class="header-logo">🏥</div>
            <div class="header-identity">
                <h1>PUSKESMAS BUGANGAN</h1>
                <p>Jl. Bugangan No. 1, Semarang, Jawa Tengah<br>
                   Telp. (024) 3541XXX &nbsp;|&nbsp; uptd.bugangan@semarangkota.go.id</p>
            </div>
        </div>
        <div class="header-body">
            <div class="doc-title">
                <h2>🧠 Laporan Hasil Skrining Kesehatan Mental</h2>
                <p>Periode: <strong><?= $dateFromFormatted ?> s.d. <?= $dateToFormatted ?></strong>
                   &nbsp;|&nbsp; Instrumen: <strong><?= htmlspecialchars($typeLabel) ?></strong></p>
            </div>
            <div class="doc-meta">
                Dicetak: <?= $printTime ?><br>
                Oleh: <?= htmlspecialchars($adminName) ?><br>
                Total Data: <?= count($results) ?> skrining
            </div>
        </div>
    </div>

    <!-- Summary cards -->
    <div class="summary-section">
        <div class="summary-grid">
            <div class="summary-card">
                <div class="num num-total"><?= count($results) ?></div>
                <div class="lbl">Total Skrining</div>
            </div>
            <div class="summary-card">
                <div class="num num-low"><?= $low ?></div>
                <div class="lbl">Risiko Rendah</div>
            </div>
            <div class="summary-card">
                <div class="num num-medium"><?= $medium ?></div>
                <div class="lbl">Risiko Sedang</div>
            </div>
            <div class="summary-card">
                <div class="num num-high"><?= $high ?></div>
                <div class="lbl">Risiko Tinggi</div>
            </div>
            <div class="summary-card">
                <div class="num num-critical"><?= $critical ?></div>
                <div class="lbl">Kritis</div>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="table-section">
        <h3>Daftar Hasil Skrining</h3>
        <?php if (empty($results)): ?>
            <div class="empty-state">📋 Tidak ada data skrining pada periode ini.</div>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th style="width:40px">No</th>
                    <th>Tanggal</th>
                    <th>Nama Pasien</th>
                    <th>No HP</th>
                    <th>Usia</th>
                    <th>Instrumen</th>
                    <th>Skor</th>
                    <th>Hasil</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $typeBadges   = ['EPDS'=>'badge-epds','PHQ2-GAD2'=>'badge-phq','MMYS-TEEN'=>'badge-mmys-teen','MMYS-CHILD'=>'badge-mmys-child'];
                $resultBadges = ['RENDAH'=>'badge-low','SEDANG'=>'badge-medium','TINGGI'=>'badge-high','KRITIS'=>'badge-critical'];
                foreach ($results as $i => $r):
                    $typeClass   = $typeBadges[$r['screening_type']] ?? '';
                    $resultClass = $resultBadges[$r['interpretation']] ?? '';
                ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><?= date('d/m/Y H:i', strtotime($r['created_at'])) ?></td>
                    <td><strong><?= htmlspecialchars($r['respondent_name']) ?></strong></td>
                    <td><?= htmlspecialchars($r['respondent_phone'] ?? '-') ?></td>
                    <td><?= $r['respondent_age'] ? $r['respondent_age'] . ' th' : '-' ?></td>
                    <td><span class="badge <?= $typeClass ?>"><?= $r['screening_type'] ?></span></td>
                    <td><strong><?= $r['total_score'] ?></strong></td>
                    <td><span class="badge <?= $resultClass ?>"><?= $r['interpretation'] ?></span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <!-- Footer / signature -->
    <div class="doc-footer">
        <div class="footer-left">
            <p><strong>Catatan:</strong></p>
            <p>• Dokumen ini bersifat rahasia dan hanya untuk keperluan internal Puskesmas Bugangan.</p>
            <p>• Data diambil dari sistem skrining digital Bugangan Mental Care.</p>
            <p>• Dicetak pada: <?= $printTime ?></p>
        </div>
        <div class="signature-block">
            <p class="sig-title">Semarang, <?= date('d F Y') ?></p>
            <div class="sig-name">
                <?= htmlspecialchars($adminName) ?><br>
                <small style="font-weight:400;color:#64748b">Administrator Puskesmas Bugangan</small>
            </div>
        </div>
    </div>

</div>
</body>
</html>
