<?php
session_start();
include 'baglan.php';

if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit(); }

$user_id = $_SESSION['user_id'];

// Hocanın yazdığı antrenman planlarını çek
$sorgu = $conn->prepare("SELECT ep.*, k.ad_soyad as hoca_adi 
                         FROM egzersiz_planlari ep 
                         LEFT JOIN kullanicilar k ON ep.hoca_id = k.id 
                         WHERE ep.user_id = ? 
                         ORDER BY ep.kayit_tarihi DESC");
$sorgu->execute([$user_id]);
$planlar = $sorgu->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Egzersizlerim</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; background: #f4f7fe; margin: 0; display: flex; }
        .sidebar { width: 240px; background: white; height: 100vh; padding: 30px 20px; box-shadow: 2px 0 10px rgba(0,0,0,0.05); position: fixed; }
        .content { margin-left: 280px; padding: 40px; width: 100%; }
        .plan-card { background: white; padding: 25px; border-radius: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.06); margin-bottom: 25px; border-top: 6px solid #0984e3; }
        .menu-item { display: block; padding: 12px; color: #636e72; text-decoration: none; border-radius: 12px; margin-bottom: 10px; }
        .menu-item.active { background: #f0f7ff; color: #0984e3; font-weight: 600; }
    </style>
</head>
<body>

<div class="sidebar">
    <div style="color:#0984e3; font-size:22px; font-weight:600; margin-bottom:40px;">🩺 Sağlık Takip</div>
    <a href="panel.php" class="menu-item">🏠 Özet Paneli</a>
    <a href="beslenme.php" class="menu-item">🥗 Beslenme Planım</a>
    <a href="egzersiz.php" class="menu-item active">🏋️ Egzersizlerim</a>
    <a href="cikis.php" class="menu-item" style="color:#d63031;">🚪 Çıkış Yap</a>
</div>

<div class="content">
    <h1>🏋️ Antrenman Programlarım</h1>
    <?php foreach ($planlar as $p): ?>
        <div class="plan-card">
            <strong>👟 Hoca: <?php echo htmlspecialchars($p['hoca_adi']); ?></strong>
            <br><small>📅 <?php echo date('d.m.Y H:i', strtotime($p['kayit_tarihi'])); ?></small>
            <p><?php echo nl2br(htmlspecialchars($p['antrenman_notu'])); ?></p>
        </div>
    <?php endforeach; ?>
</div>

</body>
</html>