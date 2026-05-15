<?php
session_start();
include 'baglan.php';

if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit(); }
$user_id = $_SESSION['user_id'];

// Kullanıcının rozetlerini çek
$sorgu = $conn->prepare("
    SELECT r.*, kr.kazanma_tarihi 
    FROM rozetler r 
    JOIN kullanici_rozetleri kr ON r.id = kr.rozet_id 
    WHERE kr.user_id = ? 
    ORDER BY kr.kazanma_tarihi DESC
");
$sorgu->execute([$user_id]);
$rozetler = $sorgu->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Rozetlerim | Sağlık Takibi</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; background: #f8fafc; display: flex; margin:0; }
        .sidebar { width: 260px; background: white; height: 100vh; padding: 30px 20px; box-shadow: 2px 0 10px rgba(0,0,0,0.05); position: fixed; }
        .main { margin-left: 300px; padding: 40px; width: 100%; }
        .badge-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 20px; }
        .badge-card { background: white; padding: 20px; border-radius: 20px; text-align: center; box-shadow: 0 4px 6px rgba(0,0,0,0.05); border-top: 5px solid #6c5ce7; }
        .badge-icon { font-size: 40px; margin-bottom: 10px; display: block; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div style="font-size:28px; font-weight:800; margin-bottom:40px; color:#111827; letter-spacing:-1px;">
    <span style="color:#ef4444;">❤</span> wellX
</div>
        <a href="panel.php" style="display:block; margin-bottom:15px; text-decoration:none; color:#64748b;">🏠 Özet Panel</a>
        <a href="rozetlerim.php" style="display:block; margin-bottom:15px; text-decoration:none; color:#6c5ce7; font-weight:600;">🏆 Rozetlerim</a>
    </div>
    <div class="main">
        <h1>🏆 Başarı Rozetlerim</h1>
        <div class="badge-grid">
            <?php foreach($rozetler as $r): ?>
                <div class="badge-card">
                    <span class="badge-icon">
                        <?php 
                        if($r['kategori'] == 'su') echo "💧";
                        elseif($r['kategori'] == 'uyku') echo "🌙";
                        else echo "🏃";
                        ?>
                    </span>
                    <h3 style="margin:0; font-size:18px;"><?php echo $r['rozet_adi']; ?></h3>
                    <p style="font-size:12px; color:#64748b;"><?php echo $r['rozet_aciklamasi']; ?></p>
                    <small style="color:#94a3b8;"><?php echo date('d.m.Y', strtotime($r['kazanma_tarihi'])); ?></small>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>