<?php
session_start();
include 'baglan.php';

if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit(); }

$user_id = $_SESSION['user_id'];
$premium_sorgu = $conn->prepare("
    SELECT is_premium
    FROM kullanicilar
    WHERE id = ?
");

$premium_sorgu->execute([$user_id]);

$kullanici_veri = $premium_sorgu->fetch(PDO::FETCH_ASSOC);

$is_premium = $kullanici_veri['is_premium'] ?? 0;

$fotolar = [];

if($is_premium == 1){

    $foto_sorgu = $conn->prepare("
        SELECT *
        FROM gelisim_fotograflari
        WHERE user_id = ?
        ORDER BY yuklenme_tarihi DESC
    ");

    $foto_sorgu->execute([$user_id]);

    $fotolar = $foto_sorgu->fetchAll(PDO::FETCH_ASSOC);
}

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
<!-- PREMIUM GELİŞİM FOTOĞRAFLARI -->

<div class="card" style="
    background: linear-gradient(135deg,#ffffff 0%,#f8fbff 100%);
    border:1px solid #eaf2ff;
    overflow:hidden;
">

    <div style="
        display:flex;
        justify-content:space-between;
        align-items:center;
        margin-bottom:25px;
    ">

        <div>

            <h2 style="
                margin:0;
                font-size:28px;
                color:#2d3436;
            ">
                📸 Premium Gelişim Arşivi
            </h2>

            <p style="
                margin-top:8px;
                color:#636e72;
                font-size:15px;
            ">
                Aylık fiziksel gelişimini profesyonel şekilde takip et.
            </p>

        </div>

        <?php if($is_premium != 1): ?>

    <a href="premium_planlar.php"
    style="
        background:linear-gradient(135deg,#f6b93b,#fa983a);
        color:white;
        padding:10px 18px;
        border-radius:14px;
        font-weight:600;
        box-shadow:0 10px 20px rgba(250,152,58,0.25);
        text-decoration:none;
        transition:0.3s;
        display:inline-block;
    ">
        👑 PREMIUM
    </a>

    <?php else: ?>

    <div style="
        background:linear-gradient(135deg,#00b894,#00cec9);
        color:white;
        padding:10px 18px;
        border-radius:14px;
        font-weight:600;
        box-shadow:0 10px 20px rgba(0,206,201,0.25);
    ">
        ✅ PREMIUM AKTİF
    </div>

    <?php endif; ?>

    </div>

    <?php if($is_premium == 1): ?>

        <form action="foto_yukle.php"
              method="POST"
              enctype="multipart/form-data"
              style="
                background:white;
                padding:25px;
                border-radius:20px;
                border:1px solid #eef2f7;
                margin-bottom:35px;
              ">

            <div style="
                font-weight:600;
                margin-bottom:15px;
                color:#2d3436;
            ">
                Yeni Gelişim Fotoğrafı Yükle
            </div>

            <input type="file"
                   name="gelisim_foto"
                   required
                   style="
                    margin-bottom:15px;
                    width:100%;
                    padding:12px;
                    border:1px solid #dfe6e9;
                    border-radius:12px;
                   ">

            <textarea name="aciklama"
                      placeholder="Örn: 2 aylık değişim / yağ oranı düşüşü..."
                      style="
                        width:100%;
                        padding:16px;
                        border:1px solid #dfe6e9;
                        border-radius:14px;
                        margin-bottom:15px;
                        resize:none;
                        height:100px;
                      "></textarea>

            <button type="submit"
                    style="
                        background:linear-gradient(135deg,#0984e3,#74b9ff);
                        color:white;
                        border:none;
                        padding:14px 28px;
                        border-radius:14px;
                        font-weight:600;
                        cursor:pointer;
                        box-shadow:0 10px 20px rgba(9,132,227,0.25);
                    ">

                Fotoğrafı Arşive Ekle

            </button>

        </form>

        <div style="
            display:grid;
            grid-template-columns:repeat(auto-fit,minmax(280px,1fr));
            gap:25px;
        ">

            <?php foreach($fotolar as $foto): ?>

                <div style="
                    background:white;
                    border-radius:24px;
                    overflow:hidden;
                    box-shadow:0 15px 30px rgba(0,0,0,0.06);
                    transition:0.3s;
                    border:1px solid #edf2f7;
                ">

                    <div style="position:relative;">

                        <img src="<?php echo $foto['foto_yolu']; ?>"
                             style="
                                width:100%;
                                height:340px;
                                object-fit:cover;
                             ">

                        <div style="
                            position:absolute;
                            top:15px;
                            right:15px;
                            background:rgba(0,0,0,0.6);
                            color:white;
                            padding:8px 12px;
                            border-radius:10px;
                            font-size:13px;
                            backdrop-filter:blur(8px);
                        ">

                            <?php echo date('d M Y', strtotime($foto['yuklenme_tarihi'])); ?>

                        </div>

                    </div>

                    <div style="padding:20px;">

                        <div style="
                            color:#2d3436;
                            font-weight:600;
                            margin-bottom:10px;
                        ">
                            Gelişim Notu
                        </div>

                        <div style="
                            color:#636e72;
                            line-height:1.7;
                            font-size:14px;
                        ">

                            <?php echo htmlspecialchars($foto['aciklama']); ?>

                        </div>

                    </div>

                </div>

            <?php endforeach; ?>

        </div>

    <?php else: ?>

        <div style="
            background:linear-gradient(135deg,#f8f9ff,#eef4ff);
            padding:60px 30px;
            border-radius:30px;
            text-align:center;
            border:1px solid #dbeafe;
        ">

            <div style="
                font-size:70px;
                margin-bottom:20px;
            ">
                🔒
            </div>

            <h2 style="
                margin-bottom:15px;
                color:#2d3436;
            ">
                Premium Özelliği
            </h2>

            <p style="
                color:#636e72;
                max-width:600px;
                margin:auto;
                line-height:1.8;
                font-size:15px;
            ">

                Fiziksel gelişim fotoğraflarını aylık olarak arşivlemek,
                değişimini profesyonel şekilde takip etmek ve özel analizlere erişmek için premium üyelik gereklidir.

            </p>

        </div>

    <?php endif; ?>

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