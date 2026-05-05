<?php
session_start();
include 'baglan.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Diyetisyenin bu danışan için yazdığı planları tarih sırasına göre çek
$sorgu = $conn->prepare("SELECT bp.*, k.ad_soyad as diyetisyen_adi 
                         FROM beslenme_planlari bp 
                         LEFT JOIN kullanicilar k ON bp.diyetisyen_id = k.id 
                         WHERE bp.user_id = ? 
                         ORDER BY bp.kayit_tarihi DESC");
$sorgu->execute([$user_id]);
$planlar = $sorgu->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Beslenme Planım | Sağlık Takibi</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #2ecc71; --bg: #f4f7fe; }
        body { font-family: 'Poppins', sans-serif; background: var(--bg); margin: 0; display: flex; }
        .sidebar { width: 240px; background: white; height: 100vh; padding: 30px 20px; box-shadow: 2px 0 10px rgba(0,0,0,0.05); position: fixed; }
        .content { margin-left: 280px; padding: 40px; width: calc(100% - 280px); }
        .plan-card { background: white; padding: 25px; border-radius: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); margin-bottom: 25px; border-top: 5px solid var(--primary); }
        .plan-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 10px; }
        .plan-date { font-size: 13px; color: #95a5a6; }
        .plan-text { white-space: pre-line; color: #2d3436; line-height: 1.6; }
        .no-plan { text-align: center; padding: 50px; background: white; border-radius: 20px; color: #95a5a6; }
        .menu-item { display: flex; align-items: center; padding: 12px; color: #636e72; text-decoration: none; border-radius: 12px; margin-bottom: 10px; }
        .menu-item.active { background: #eafaf1; color: var(--primary); font-weight: 600; }
    </style>
</head>
<body>

<div class="sidebar">
    <div style="color:var(--primary); font-size:22px; font-weight:600; margin-bottom:40px;">🩺 Sağlık Takip</div>
    <a href="panel.php" class="menu-item">🏠 Özet Paneli</a>
    <a href="beslenme.php" class="menu-item active">🥗 Beslenme Planım</a>
    <a href="egzersiz.php" class="menu-item">🏋️ Egzersizlerim</a>
    <a href="cikis.php" class="menu-item" style="color:#d63031; margin-top:40px;">🚪 Çıkış Yap</a>
</div>

<div class="content">
    <h1>🥗 Beslenme Planlarım</h1>

    <?php if (count($planlar) > 0): ?>
        <?php foreach ($planlar as $plan): ?>
            <div class="plan-card">
                <div class="plan-header">
                    <strong>🍎 Diyetisyen: <?php echo htmlspecialchars($plan['diyetisyen_adi']); ?></strong>
                    <span class="plan-date">📅 <?php echo date('d.m.Y H:i', strtotime($plan['kayit_tarihi'])); ?></span>
                </div>
                <div class="plan-text">
                    <?php echo nl2br(htmlspecialchars($plan['plan_metni'])); ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="no-plan">
            <h3>Henüz bir beslenme planınız bulunmuyor.</h3>
            <p>Diyetisyeniniz plan oluşturduğunda burada görünecektir.</p>
        </div>
    <?php endif; ?>
</div>

</body>
</html>