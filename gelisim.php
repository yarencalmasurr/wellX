<?php
session_start();
include 'baglan.php';

if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit(); }
$user_id = $_SESSION['user_id'];

// Son 10 kaydı çekelim
$sorgu = $conn->prepare("SELECT * FROM aktivite_kayitlari WHERE user_id = ? ORDER BY kayit_tarihi DESC LIMIT 10");
$sorgu->execute([$user_id]);
$veriler = array_reverse($sorgu->fetchAll(PDO::FETCH_ASSOC)); // Eskiden yeniye sırala

$tarihler = []; $kilolar = []; $sular = [];
foreach($veriler as $v) {
    $tarihler[] = date('d/m', strtotime($v['kayit_tarihi']));
    $kilolar[] = $v['guncel_kilo'];
    $sular[] = $v['su_miktari'];
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Gelişim Takibi</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #fafafa; color: #334155; padding: 40px; margin: 0; }
        .container { max-width: 900px; margin: 0 auto; }
        .header { margin-bottom: 40px; }
        .header h1 { font-size: 24px; font-weight: 600; margin: 0; }
        
        .chart-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 40px; }
        .chart-card { background: white; padding: 25px; border-radius: 16px; border: 1px solid #f1f5f9; }
        .chart-card h3 { font-size: 14px; color: #64748b; margin-top: 0; margin-bottom: 20px; font-weight: 500; text-transform: uppercase; letter-spacing: 0.5px; }
        
        .data-table { width: 100%; border-collapse: collapse; background: white; border-radius: 16px; overflow: hidden; border: 1px solid #f1f5f9; }
        .data-table th { background: #f8fafc; padding: 15px; text-align: left; font-size: 13px; color: #64748b; font-weight: 500; }
        .data-table td { padding: 15px; border-top: 1px solid #f1f5f9; font-size: 14px; }
        .weight-badge { background: #f1f5f9; padding: 4px 8px; border-radius: 6px; font-weight: 600; color: #0f172a; }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1>Gelişim Analizi</h1>
        <p style="color: #64748b; font-size: 14px;">Son 10 günlük aktivite verileriniz.</p>
    </div>

    <div class="chart-grid">
        <div class="chart-card">
            <h3>Kilo Değişimi</h3>
            <canvas id="weightChart"></canvas>
        </div>
        <div class="chart-card">
            <h3>Su Tüketimi (L)</h3>
            <canvas id="waterChart"></canvas>
        </div>
    </div>

    <div class="header" style="margin-bottom: 20px;">
        <h2 style="font-size: 18px;">Geçmiş Veriler</h2>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th>Tarih</th>
                <th>Kilo</th>
                <th>Su</th>
                <th>Uyku</th>
                <th>Kalori (Alınan)</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach(array_reverse($veriler) as $row): ?>
            <tr>
                <td><?php echo date('d.m.Y', strtotime($row['kayit_tarihi'])); ?></td>
                <td><span class="weight-badge"><?php echo $row['guncel_kilo']; ?> kg</span></td>
                <td><?php echo $row['su_miktari']; ?> L</td>
                <td><?php echo $row['uyku_suresi']; ?> Saat</td>
                <td><?php echo round($row['alinan_kalori']); ?> kcal</td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
const commonOptions = {
    responsive: true,
    plugins: { legend: { display: false } },
    scales: {
        x: { grid: { display: false }, ticks: { font: { size: 11 } } },
        y: { grid: { color: '#f1f5f9' }, border: { display: false }, ticks: { font: { size: 11 } } }
    }
};

// Kilo Grafiği
new Chart(document.getElementById('weightChart'), {
    type: 'line',
    data: {
        labels: <?php echo json_encode($tarihler); ?>,
        datasets: [{
            data: <?php echo json_encode($kilolar); ?>,
            borderColor: '#0f172a',
            borderWidth: 2,
            pointRadius: 0,
            tension: 0.3
        }]
    },
    options: commonOptions
});

// Su Grafiği
new Chart(document.getElementById('waterChart'), {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($tarihler); ?>,
        datasets: [{
            data: <?php echo json_encode($sular); ?>,
            backgroundColor: '#38bdf8',
            borderRadius: 4
        }]
    },
    options: commonOptions
});
</script>

</body>
</html>