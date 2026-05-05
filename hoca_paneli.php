<?php
session_start();
include 'baglan.php';

// Güvenlik Kontrolü
if (!isset($_SESSION['user_id']) || ($_SESSION['rol'] != 'hoca')) {
    header("Location: index.php"); 
    exit();
}

$hoca_id = $_SESSION['user_id'];
$bugun = date('Y-m-d');

try {
    // 1. Sadece bu hocaya bağlı danışanları ve bugünkü spor sürelerini çekiyoruz
    $sorgu = $conn->prepare("
        SELECT k.id, k.ad_soyad, k.email,
        (SELECT SUM(spor_suresi) FROM aktivite_kayitlari WHERE user_id = k.id AND kayit_tarihi = ?) as bugunku_spor
        FROM kullanicilar k 
        WHERE k.rol = 'danisan' AND k.hoca_id = ?
    ");
    $sorgu->execute([$bugun, $hoca_id]);
    $danisanlar = $sorgu->fetchAll(PDO::FETCH_ASSOC);

    // 2. Hocanın paylaştığı son duyuruyu/antrenmanı çekiyoruz
    $duyuru_sorgu = $conn->prepare("SELECT * FROM gunun_antrenmani WHERE hoca_id = ? ORDER BY id DESC LIMIT 1");
    $duyuru_sorgu->execute([$hoca_id]);
    $son_duyuru = $duyuru_sorgu->fetch();
} catch (PDOException $e) {
    die("Veritabanı hatası oluştu: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Hoca Yönetim Paneli</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --primary: #2c3e50; --accent: #3498db; --danger: #e74c3c; --bg: #f4f7f6; }
        body { background: var(--bg); font-family: 'Poppins', sans-serif; margin: 0; display: flex; color: #2c3e50; }
        
        /* Sidebar */
        .sidebar { width: 260px; background: var(--primary); height: 100vh; color: white; padding: 30px; position: fixed; box-shadow: 4px 0 15px rgba(0,0,0,0.1); }
        .sidebar h2 { font-size: 20px; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 15px; }
        .sidebar a { display: block; color: white; text-decoration: none; margin-top: 20px; padding: 10px; border-radius: 8px; transition: 0.3s; }
        .sidebar a:hover { background: rgba(255,255,255,0.1); }

        /* Main Content */
        .main { margin-left: 320px; padding: 40px; width: calc(100% - 320px); }
        h1 { font-size: 26px; margin-bottom: 30px; }

        /* Kartlar */
        .card { background: white; padding: 25px; border-radius: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.03); margin-bottom: 25px; border: 1px solid #eef2f0; }
        .card h3 { margin-top: 0; display: flex; align-items: center; gap: 10px; font-size: 18px; }
        
        /* Alert */
        .alert { background: #ffeaa7; color: #d35400; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; text-transform: uppercase; }
        
        /* Form */
        textarea { width: 100%; height: 80px; margin: 10px 0; border-radius: 10px; border: 1px solid #ddd; padding: 10px; resize: none; box-sizing: border-box; }
        .btn { background: var(--accent); color: white; border: none; padding: 12px 25px; border-radius: 10px; cursor: pointer; font-weight: 600; width: 100%; transition: 0.3s; }
        .btn:hover { background: #2980b9; transform: translateY(-2px); }
        .tarif-box { background: #ebf5fb; border: 1px dashed var(--accent); }
    </style>
</head>
<body>

<div class="sidebar">
    <h2>🏋️ Hoca Paneli</h2>
    <p>Hoş Geldiniz, <br><strong><?php echo htmlspecialchars($_SESSION['ad_soyad']); ?></strong></p>
    <nav>
        <a href="hoca_paneli.php"><i class="fas fa-dumbbell"></i> Danışan Takibi</a>
        <a href="cikis.php" style="color: var(--danger);"><i class="fas fa-sign-out-alt"></i> Çıkış Yap</a>
    </nav>
</div>

<div class="main">
    <div class="card tarif-box">
        <h3>📢 Günün Antrenman Duyurusunu Paylaş</h3>
        <form action="islem_v2.php?is=antrenman_duyuru_kaydet" method="POST">
            <input type="text" name="duyuru_baslik" placeholder="Başlık" required style="width:100%; padding:10px; border-radius:8px; border:1px solid #ddd; margin-bottom:10px; box-sizing:border-box;">
            <textarea name="duyuru_icerik" placeholder="Günün antrenman notu veya duyuru..." required></textarea>
            <button type="submit" class="btn" style="background:var(--primary);">Duyuruyu Yayınla</button>
        </form>
    </div>

    <h1>Danışan Spor Takip Listesi</h1>

    <?php if($danisanlar): ?>
        <?php foreach($danisanlar as $d): ?>
            <div class="card">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <h3>👤 <?php echo htmlspecialchars($d['ad_soyad']); ?></h3>
                    <span style="font-size: 12px; color: #94a3b8;"><?php echo htmlspecialchars($d['email'] ?? 'Email yok'); ?></span>
                </div>
                
                <p>Bugünkü Spor Süresi: <strong><?php echo $d['bugunku_spor'] ?? 0; ?> dakika</strong> 
                <?php if(($d['bugunku_spor'] ?? 0) < 30): ?>
                    <span class="alert">⚠️ Hedefin Altında!</span>
                <?php endif; ?>
                </p>
                
                <form action="islem_v2.php?is=egzersiz_yaz" method="POST">
                    <input type="hidden" name="danisan_id" value="<?php echo $d['id']; ?>">
                    <textarea name="antrenman_notu" placeholder="<?php echo $d['ad_soyad']; ?> için antrenman notu yazın..."></textarea>
                    <button type="submit" class="btn">Notu Gönder</button>
                </form>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="card" style="text-align: center; padding: 30px;">
            <p style="color: #94a3b8;">Henüz size atanmış bir danışan bulunmuyor.</p>
        </div>
    <?php endif; ?>
</div>

</body>
</html>