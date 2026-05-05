<?php
session_start();
include 'baglan.php';

if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit(); }

$user_id = $_SESSION['user_id'];

// GRAFİK İÇİN SON 7 GÜNÜN VERİLERİNİ ÇEK
$grafik_sorgu = $conn->prepare("SELECT kayit_tarihi, guncel_kilo, alinan_kalori 
                                FROM aktivite_kayitlari 
                                WHERE user_id = ? 
                                ORDER BY kayit_tarihi ASC LIMIT 7");
$grafik_sorgu->execute([$user_id]);
$grafik_verileri = $grafik_sorgu->fetchAll(PDO::FETCH_ASSOC);

// JS için dizileri hazırla
$tarihler = []; $kilolar = []; $kaloriler = [];
foreach($grafik_verileri as $gv) {
    $tarihler[] = date('d M', strtotime($gv['kayit_tarihi']));
    $kilolar[]  = $gv['guncel_kilo'];
    $kaloriler[] = $gv['alinan_kalori'];
}

// HAFTALIK ÖZET LİSTESİ (Tablo için)
$sorgu = $conn->prepare("SELECT 
    YEARWEEK(kayit_tarihi, 1) as hafta,
    MIN(kayit_tarihi) as hafta_baslangic,
    AVG(guncel_kilo) as ortalama_kilo,
    SUM(alinan_kalori) as toplam_alinan,
    SUM(yakilan_kalori) as toplam_yakilan
    FROM aktivite_kayitlari 
    WHERE user_id = ? 
    GROUP BY hafta ORDER BY hafta DESC LIMIT 4");
$sorgu->execute([$user_id]);
$gelisimler = $sorgu->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Gelişim Grafiği | Sağlık Takibi</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script> <!-- Grafik Kütüphanesi -->
    <style>
        body { font-family: 'Poppins', sans-serif; background: #f4f7fe; margin: 0; display: flex; }
        .sidebar { width: 240px; background: white; height: 100vh; padding: 30px 20px; box-shadow: 2px 0 10px rgba(0,0,0,0.05); position: fixed; }
        .content { margin-left: 280px; padding: 40px; width: calc(100% - 280px); }
        .card { background: white; padding: 25px; border-radius: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); margin-bottom: 25px; }
        .menu-item { display: block; padding: 12px; color: #636e72; text-decoration: none; border-radius: 12px; margin-bottom: 10px; }
        .menu-item.active { background: #f0f7ff; color: #0984e3; font-weight: 600; }
        .chart-container { position: relative; height: 300px; width: 100%; }
    </style>
</head>
<body>

<div class="sidebar">
    <div style="color:#0984e3; font-size:22px; font-weight:600; margin-bottom:40px;">🩺 Sağlık Takip</div>
    <a href="panel.php" class="menu-item">🏠 Özet Paneli</a>
    <a href="beslenme.php" class="menu-item">🥗 Beslenme Planım</a>
    <a href="egzersiz.php" class="menu-item">🏋️ Egzersizlerim</a>
    <a href="gelisim.php" class="menu-item active">📈 Gelişim Takibi</a>
    <a href="cikis.php" class="menu-item" style="color:#d63031; margin-top:30px;">🚪 Çıkış Yap</a>
</div>

<div class="content">
    <h1>📈 Gelişim Analizi</h1>

    <!-- GRAFİK KARTI -->
    <div class="card">
        <h3>Son 7 Günlük Kilo Değişimi</h3>
        <div class="chart-container">
            <canvas id="kiloChart"></canvas>
        </div>
    </div>

    <!-- HAFTALIK ÖZET TABLOSU -->
    <div class="card">
        <h3>📊 Haftalık Özet Tablosu</h3>
        <table style="width:100%; border-collapse: collapse; margin-top:10px;">
            <thead style="background: #f8f9fb; text-align: left;">
                <tr>
                    <th style="padding:12px;">Hafta</th>
                    <th>Ort. Kilo</th>
                    <th>Top. Kalori</th>
                    <th>Top. Yakılan</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($gelisimler as $g): ?>
                <tr style="border-bottom: 1px solid #eee;">
                    <td style="padding:12px;"><?php echo date('d M', strtotime($g['hafta_baslangic'])); ?></td>
                    <td><strong><?php echo number_format($g['ortalama_kilo'], 1); ?> kg</strong></td>
                    <td><?php echo number_format($g['toplam_alinan']); ?> kcal</td>
                    <td><?php echo number_format($g['toplam_yakilan']); ?> kcal</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// Chart.js Ayarları
const ctx = document.getElementById('kiloChart').getContext('2d');
const kiloChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode($tarihler); ?>,
        datasets: [{
            label: 'Kilo Değişimi (kg)',
            data: <?php echo json_encode($kilolar); ?>,
            borderColor: '#0984e3',
            backgroundColor: 'rgba(9, 132, 227, 0.1)',
            borderWidth: 3,
            fill: true,
            tension: 0.4, // Çizgiyi yumuşatır
            pointRadius: 5
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: { beginAtZero: false }
        }
    }
});
</script>

</body>
</html>