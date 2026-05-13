<?php
/**
 * Proje: saglik_portali
 * Dosya: egzersiz.php
 * Açıklama: Danışanın sadece bugüne ait egzersiz/antrenman planlarını gördüğü ve bildirimlerin temizlendiği sayfa.
 */

session_start();
include 'baglan.php';

// Oturum kontrolü
if (!isset($_SESSION['user_id'])) { 
    header("Location: index.php"); 
    exit(); 
}

$user_id = $_SESSION['user_id'];

// --- BİLDİRİMİ OKUNDU YAP (YENİ EKLENEN KISIM) ---
// Kullanıcı bu sayfaya girdiği an, bugünkü tüm okunmamış egzersiz planlarını okundu (1) yapar.
// Böylece panel.php'deki mavi bildirim kutusu kaybolur.
try {
    $update = $conn->prepare("UPDATE egzersiz_planlari SET okundu = 1 WHERE user_id = ? AND DATE(kayit_tarihi) = CURDATE()");
    $update->execute([$user_id]);
} catch (PDOException $e) {
    // Hata durumunda sessizce devam eder
}

/**
 * BUGÜNÜN PLANLARINI ÇEK
 */
$sorgu = $conn->prepare("SELECT ep.*, k.ad_soyad as hoca_adi 
                         FROM egzersiz_planlari ep 
                         LEFT JOIN kullanicilar k ON ep.hoca_id = k.id 
                         WHERE ep.user_id = ? 
                         AND DATE(ep.kayit_tarihi) = CURDATE()
                         ORDER BY ep.kayit_tarihi DESC");
$sorgu->execute([$user_id]);
$planlar = $sorgu->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Egzersizlerim | Sağlık Takibi</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <style>
        :root { --blue: #0984e3; --bg: #f4f7fe; }
        body { font-family: 'Poppins', sans-serif; background: var(--bg); margin: 0; display: flex; }
        .sidebar { width: 240px; background: white; height: 100vh; padding: 30px 20px; box-shadow: 2px 0 10px rgba(0,0,0,0.05); position: fixed; }
        .content { margin-left: 280px; padding: 40px; width: calc(100% - 280px); }
        .plan-card { background: white; padding: 25px; border-radius: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.06); margin-bottom: 25px; border-top: 6px solid var(--blue); }
        .plan-header { border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 15px; }
        .menu-item { display: block; padding: 12px; color: #636e72; text-decoration: none; border-radius: 12px; margin-bottom: 10px; transition: 0.3s; }
        .menu-item:hover { background: #f0f7ff; }
        .menu-item.active { background: #f0f7ff; color: var(--blue); font-weight: 600; }
        .no-plan { text-align: center; padding: 50px; background: white; border-radius: 20px; color: #95a5a6; }
    </style>
</head>
<body>

<div class="sidebar">
    <div style="color:var(--blue); font-size:22px; font-weight:600; margin-bottom:40px;">🩺 Sağlık Takip</div>
    <a href="panel.php" class="menu-item">🏠 Özet Paneli</a>
    <a href="beslenme.php" class="menu-item">🥗 Beslenme Planım</a>
    <a href="egzersiz.php" class="menu-item active">🏋️ Egzersizlerim</a>
    <a href="cikis.php" class="menu-item" style="color:#d63031; margin-top:40px;">🚪 Çıkış Yap</a>
</div>

<div class="content">
    <h1>🏋️ Bugünün Antrenman Programları</h1>
    
    <?php if (count($planlar) > 0): ?>
        <?php foreach ($planlar as $p): ?>
            <div class="plan-card">
                <div class="plan-header">
                    <strong>👟 Hoca: <?php echo htmlspecialchars($p['hoca_adi']); ?></strong>
                    <br><small style="color: #95a5a6;">📅 <?php echo date('d.m.Y H:i', strtotime($p['kayit_tarihi'])); ?></small>
                </div>
                <p style="white-space: pre-line; color: #2d3436; line-height: 1.6;">
                    <?php 
                        // Veritabanındaki gerçek sütun adına göre (antrenman_notu) gösterilir.
                        echo nl2br(htmlspecialchars($p['antrenman_notu'])); 
                    ?>
                </p>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="no-plan">
            <h3>Bugün için planlanmış bir antrenmanınız yok.</h3>
            <p>Hocanız yeni bir program eklediğinde burada görünecektir.</p>
        </div>
    <?php endif; ?>
</div>

</body>
</html>