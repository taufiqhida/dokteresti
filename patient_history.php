<?php
session_start();

// Guard
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin.php');
    exit;
}

require_once __DIR__ . '/config/database.php';

$phone = trim($_GET['phone'] ?? '');
if (!$phone) {
    header('Location: admin.php');
    exit;
}

try {
    $pdo = getDBConnection();

    // All screenings for this phone number
    $stmt = $pdo->prepare("SELECT * FROM screening_results WHERE respondent_phone = ? ORDER BY created_at ASC");
    $stmt->execute([$phone]);
    $records = $stmt->fetchAll();

    if (empty($records)) {
        header('Location: admin.php');
        exit;
    }

    $patientName   = $records[0]['respondent_name'];
    $patientAge    = $records[0]['respondent_age'];
    $latestRecord  = end($records);
    $totalSkrining = count($records);

    // Chart data
    $chartLabels = [];
    $chartScores = [];
    $chartColors = [];

    $colorMap = [
        'RENDAH' => 'rgba(16,185,129,0.85)',
        'SEDANG' => 'rgba(245,158,11,0.85)',
        'TINGGI' => 'rgba(239,68,68,0.85)',
        'KRITIS' => 'rgba(153,27,27,0.85)',
    ];

    foreach ($records as $r) {
        $chartLabels[] = date('d/m/Y', strtotime($r['created_at']));
        $chartScores[] = (int)$r['total_score'];
        $chartColors[] = $colorMap[$r['interpretation']] ?? 'rgba(99,102,241,0.85)';
    }

    // Risk level counts
    $riskCount = ['RENDAH' => 0, 'SEDANG' => 0, 'TINGGI' => 0, 'KRITIS' => 0];
    foreach ($records as $r) {
        $key = $r['interpretation'];
        if (isset($riskCount[$key])) $riskCount[$key]++;
    }

} catch (Exception $e) {
    die('Error: ' . $e->getMessage());
}

// Format WhatsApp link
function formatWaLink(string $phone, string $name): string {
    $digits = preg_replace('/\D/', '', $phone);
    if (str_starts_with($digits, '0')) {
        $digits = '62' . substr($digits, 1);
    } elseif (!str_starts_with($digits, '62')) {
        $digits = '62' . $digits;
    }
    $msg = urlencode("Halo $name, kami dari Puskesmas Bugangan ingin menindaklanjuti hasil skrining kesehatan mental Anda. Apakah Anda bersedia untuk berkonsultasi lebih lanjut?");
    return "https://wa.me/$digits?text=$msg";
}

$waLink = formatWaLink($phone, $patientName);

$typeBadges   = ['EPDS'=>'badge-epds','PHQ2-GAD2'=>'badge-phq','MMYS-TEEN'=>'badge-mmys-teen','MMYS-CHILD'=>'badge-mmys-child'];
$resultBadges = ['RENDAH'=>'badge-low','SEDANG'=>'badge-medium','TINGGI'=>'badge-high','KRITIS'=>'badge-critical'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Pasien — <?= htmlspecialchars($patientName) ?></title>
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
            --card: #ffffff;
            --border: #e2e8f0;
            --radius: 14px;
            --shadow: 0 4px 20px rgba(0,0,0,.08);
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: var(--bg); color: var(--text); min-height: 100vh; }

        /* Header */
        .header {
            background: linear-gradient(135deg, #1e1b4b, #4f46e5 60%, #7c3aed);
            color: white;
            padding: 1.5rem 2.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
        }
        .header-left { display: flex; align-items: center; gap: 1rem; }
        .header-icon {
            width: 52px; height: 52px;
            background: rgba(255,255,255,.15);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem;
            border: 2px solid rgba(255,255,255,.3);
        }
        .header-info h1 { font-size: 1.3rem; font-weight: 700; margin-bottom: 0.2rem; }
        .header-info p  { font-size: 0.82rem; opacity: .8; }
        .header-actions { display: flex; gap: 0.75rem; flex-wrap: wrap; }
        .btn-back {
            background: rgba(255,255,255,.15);
            color: white;
            border: 1px solid rgba(255,255,255,.3);
            padding: 0.6rem 1.25rem;
            border-radius: 8px;
            font-weight: 500;
            text-decoration: none;
            font-size: 0.88rem;
            transition: background .2s;
        }
        .btn-back:hover { background: rgba(255,255,255,.25); }
        .btn-wa {
            background: #22c55e;
            color: white;
            border: none;
            padding: 0.6rem 1.25rem;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            font-size: 0.88rem;
            display: flex;
            align-items: center;
            gap: 0.4rem;
            transition: background .2s;
        }
        .btn-wa:hover { background: #16a34a; }

        .main { padding: 2rem 2.5rem; max-width: 1200px; margin: 0 auto; }

        /* Info cards row */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-bottom: 1.75rem;
        }
        .info-card {
            background: var(--card);
            border-radius: var(--radius);
            padding: 1.25rem;
            box-shadow: var(--shadow);
            text-align: center;
            border-top: 4px solid transparent;
        }
        .info-card.total   { border-top-color: #6366f1; }
        .info-card.low     { border-top-color: #10b981; }
        .info-card.medium  { border-top-color: #f59e0b; }
        .info-card.high    { border-top-color: #ef4444; }
        .info-card.critical{ border-top-color: #991b1b; }
        .info-num {
            font-size: 2rem;
            font-weight: 800;
            line-height: 1;
            margin-bottom: 0.3rem;
        }
        .info-card.total .info-num   { color: #6366f1; }
        .info-card.low .info-num     { color: #10b981; }
        .info-card.medium .info-num  { color: #f59e0b; }
        .info-card.high .info-num    { color: #ef4444; }
        .info-card.critical .info-num{ color: #991b1b; }
        .info-lbl { font-size: 0.75rem; color: var(--text-light); font-weight: 500; }

        /* Latest risk badge */
        .latest-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.35rem 0.9rem;
            border-radius: 50px;
            font-size: 0.78rem;
            font-weight: 700;
            margin-left: 0.5rem;
        }
        .latest-badge.rendah  { background: #d1fae5; color: #065f46; }
        .latest-badge.sedang  { background: #fef3c7; color: #92400e; }
        .latest-badge.tinggi  { background: #fee2e2; color: #991b1b; }
        .latest-badge.kritis  { background: #991b1b; color: white; }

        /* Charts row */
        .charts-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.5rem;
            margin-bottom: 1.75rem;
        }
        .chart-card {
            background: var(--card);
            border-radius: var(--radius);
            padding: 1.5rem;
            box-shadow: var(--shadow);
        }
        .chart-card h3 {
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--text-light);
            margin-bottom: 1.25rem;
            font-weight: 600;
        }
        .chart-wrap { position: relative; height: 280px; }

        /* Table */
        .table-card {
            background: var(--card);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            overflow: hidden;
        }
        .table-title {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .table-title h3 { font-size: 1rem; font-weight: 700; }
        .table-title span { font-size: 0.82rem; color: var(--text-light); }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 0.875rem 1.25rem; text-align: left; border-bottom: 1px solid var(--border); }
        th { background: var(--bg); font-weight: 600; font-size: 0.78rem; text-transform: uppercase; color: var(--text-light); }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: #f8fafc; }
        .badge { display: inline-block; padding: 0.2rem 0.65rem; border-radius: 50px; font-size: 0.75rem; font-weight: 700; }
        .badge-epds       { background: #e0e7ff; color: #3730a3; }
        .badge-phq        { background: #d1fae5; color: #065f46; }
        .badge-mmys-teen  { background: #fef3c7; color: #92400e; }
        .badge-mmys-child { background: #fce7f3; color: #9d174d; }
        .badge-low        { background: #d1fae5; color: #065f46; }
        .badge-medium     { background: #fef3c7; color: #92400e; }
        .badge-high       { background: #fee2e2; color: #991b1b; }
        .badge-critical   { background: #991b1b; color: white; }

        .trend-arrow {
            font-size: 1rem;
            margin-left: 0.25rem;
        }
        .trend-up   { color: #ef4444; }
        .trend-down { color: #10b981; }
        .trend-same { color: #94a3b8; }

        @media (max-width: 768px) {
            .main { padding: 1rem; }
            .header { padding: 1rem 1.25rem; }
            .info-grid { grid-template-columns: repeat(3, 1fr); }
        }
    </style>
</head>
<body>

<header class="header">
    <div class="header-left">
        <div class="header-icon">👤</div>
        <div class="header-info">
            <h1>
                <?= htmlspecialchars($patientName) ?>
                <?php
                $latestInterp = strtolower($latestRecord['interpretation']);
                $interpLabel  = $latestRecord['interpretation'];
                ?>
                <span class="latest-badge <?= $latestInterp ?>">
                    ● Terbaru: <?= $interpLabel ?>
                </span>
            </h1>
            <p>
                📱 <?= htmlspecialchars($phone) ?>
                <?php if ($patientAge): ?> &nbsp;|&nbsp; 🎂 <?= $patientAge ?> Tahun<?php endif; ?>
                &nbsp;|&nbsp; 📋 <?= $totalSkrining ?>x Skrining
            </p>
        </div>
    </div>
    <div class="header-actions">
        <a href="admin.php" class="btn-back">← Dashboard</a>
        <a href="<?= htmlspecialchars($waLink) ?>" target="_blank" class="btn-wa">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
            </svg>
            Follow-up WA
        </a>
    </div>
</header>

<main class="main">

    <!-- Info cards -->
    <div class="info-grid">
        <div class="info-card total">
            <div class="info-num"><?= $totalSkrining ?></div>
            <div class="info-lbl">Total Skrining</div>
        </div>
        <div class="info-card low">
            <div class="info-num"><?= $riskCount['RENDAH'] ?></div>
            <div class="info-lbl">Rendah</div>
        </div>
        <div class="info-card medium">
            <div class="info-num"><?= $riskCount['SEDANG'] ?></div>
            <div class="info-lbl">Sedang</div>
        </div>
        <div class="info-card high">
            <div class="info-num"><?= $riskCount['TINGGI'] ?></div>
            <div class="info-lbl">Tinggi</div>
        </div>
        <div class="info-card critical">
            <div class="info-num"><?= $riskCount['KRITIS'] ?></div>
            <div class="info-lbl">Kritis</div>
        </div>
    </div>

    <!-- Perkembangan Skor Chart -->
    <div class="chart-card" style="margin-bottom:1.75rem">
        <h3>📈 Perkembangan Skor dari Waktu ke Waktu</h3>
        <div class="chart-wrap">
            <canvas id="progressChart"></canvas>
        </div>
    </div>

    <!-- History table -->
    <div class="table-card">
        <div class="table-title">
            <h3>📋 Riwayat Skrining Lengkap</h3>
            <span><?= $totalSkrining ?> data</span>
        </div>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Tanggal</th>
                    <th>Instrumen</th>
                    <th>Skor</th>
                    <th>Hasil</th>
                    <th>Tren</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($records as $i => $r):
                    $typeClass   = $typeBadges[$r['screening_type']] ?? '';
                    $resultClass = $resultBadges[$r['interpretation']] ?? '';
                    // Trend vs previous
                    $trendIcon = '';
                    if ($i > 0) {
                        $prevScore = (int)$records[$i-1]['total_score'];
                        $currScore = (int)$r['total_score'];
                        if ($currScore > $prevScore)       $trendIcon = '<span class="trend-arrow trend-up">↑</span>';
                        elseif ($currScore < $prevScore)   $trendIcon = '<span class="trend-arrow trend-down">↓</span>';
                        else                               $trendIcon = '<span class="trend-arrow trend-same">→</span>';
                    }
                ?>
                <tr>
                    <td><?= $totalSkrining - $i ?></td>
                    <td><?= date('d/m/Y H:i', strtotime($r['created_at'])) ?></td>
                    <td><span class="badge <?= $typeClass ?>"><?= $r['screening_type'] ?></span></td>
                    <td><strong><?= $r['total_score'] ?></strong></td>
                    <td><span class="badge <?= $resultClass ?>"><?= $r['interpretation'] ?></span></td>
                    <td><?= $trendIcon ?: '—' ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</main>

<script>
const labels = <?= json_encode($chartLabels) ?>;
const scores = <?= json_encode($chartScores) ?>;
const bgColors = <?= json_encode($chartColors) ?>;

const ctx = document.getElementById('progressChart').getContext('2d');

// Gradient fill
const grad = ctx.createLinearGradient(0, 0, 0, 280);
grad.addColorStop(0, 'rgba(99,102,241,0.25)');
grad.addColorStop(1, 'rgba(99,102,241,0)');

new Chart(ctx, {
    type: 'line',
    data: {
        labels,
        datasets: [{
            label: 'Skor Skrining',
            data: scores,
            borderColor: '#6366f1',
            backgroundColor: grad,
            borderWidth: 2.5,
            pointBackgroundColor: bgColors,
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            pointRadius: 8,
            pointHoverRadius: 10,
            fill: true,
            tension: 0.35,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: ctx => ` Skor: ${ctx.parsed.y}`,
                    afterLabel: (ctx) => {
                        const riskLabels = ['RENDAH','SEDANG','TINGGI','KRITIS'];
                        // Map by color index
                        return '';
                    }
                }
            }
        },
        scales: {
            x: {
                grid: { display: false },
                ticks: { font: { size: 11 }, color: '#64748b' }
            },
            y: {
                beginAtZero: true,
                grid: { color: 'rgba(0,0,0,.05)' },
                ticks: { font: { size: 11 }, color: '#64748b', stepSize: 1 }
            }
        }
    }
});
</script>
</body>
</html>
