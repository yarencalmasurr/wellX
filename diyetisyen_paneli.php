<?php
session_start();
include 'baglan.php';

// Güvenlik: Giriş yapmamışsa index'e at
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] != 'diyetisyen') {
    header("Location: index.php"); exit;
}

$d_id = $_SESSION['user_id'];
$bugun = date('Y-m-d');

// Diyetisyene bağlı danışanları ve bugün kaç kalori aldıklarını getir
$sorgu = $conn->prepare("
    SELECT k.id, k.ad_soyad, k.eposta, 
    (SELECT SUM(alinan_kalori) FROM aktivite_kayitlari WHERE user_id = k.id AND kayit_tarihi = ?) as kalori 
    FROM kullanicilar k 
    WHERE k.rol = 'danisan' AND k.diyetisyen_id = ?
");
$sorgu->execute([$bugun, $d_id]);
$danisanlar = $sorgu->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Diyetisyen Yönetim Merkezi</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: 'Poppins', sans-serif; background: #f0f7f4; margin: 0; display: flex; }
        .sidebar { width: 260px; background: #1a3a3a; height: 100vh; color: white; padding: 30px; position: fixed; }
        .main { margin-left: 320px; padding: 40px; width: calc(100% - 320px); }
        .card { background: white; padding: 25px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); margin-bottom: 25px; border: 1px solid #e1e8e5; }
        .tarif-box { background: #e6f4ea; border: 2px dashed #34a853; }
        input, textarea { width: 100%; padding: 12px; margin: 10px 0; border-radius: 10px; border: 1px solid #ccd6d1; box-sizing: border-box; font-family: inherit; }
        .btn { background: #34a853; color: white; border: none; padding: 14px; border-radius: 10px; cursor: pointer; width: 100%; font-weight: 600; transition: 0.3s; }
        .btn:hover { background: #2d8e47; }
        .alert-badge { background: #ea4335; color: white; padding: 3px 10px; border-radius: 20px; font-size: 11px; }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2>🍎 Dyt. Panel</h2>
        <p>Sayın, <strong><?php echo $_SESSION['ad_soyad']; ?></strong></p>
        <hr style="opacity: 0.2; margin: 20px 0;">
        <a href="cikis.php" style="color: #ff9999; text-decoration: none;"><i class="fas fa-power-off"></i> Güvenli Çıkış</a>
    </div>

    <div class="main">
        <div class="card tarif-box">
            <h3><i class="fas fa-bullhorn"></i> Günün Tarifini/Duyurusunu Paylaş</h3>
            <form action="islem_v2.php?is=tarif_kaydet" method="POST">
                <input type="text" name="tarif_baslik" placeholder="Duyuru Başlığı (Örn: Bugün Bol Su İçelim!)" required>
                <textarea name="tarif_icerik" rows="4" placeholder="Tarif detayları veya mesajınızı buraya yazın..." required></textarea>
                <button type="submit" class="btn">Tüm Danışanlara Yayınla</button>
            </form>
        </div>

        <h2>Danışanlarınızın Günlük Durumu</h2>
        <?php foreach($danisanlar as $d): ?>
            <div class="card">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <strong><i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($d['ad_soyad']); ?></strong>
                    <span style="font-size: 12px; color: #7f8c8d;"><?php echo htmlspecialchars($d['eposta']); ?></span>
                </div>
                <p>Bugün Alınan: <strong><?php echo $d['kalori'] ?? 0; ?> kcal</strong> 
                <?php if(($d['kalori'] ?? 0) > 2200): ?> <span class="alert-badge">Sınır Aşıldı</span> <?php endif; ?>
                </p>
                
                <form action="islem_v2.php?is=plan_yaz" method="POST">
                    <input type="hidden" name="danisan_id" value="<?php echo $d['id']; ?>">
                    <textarea name="plan_metni" placeholder="Bu danışana özel not bırakın..."></textarea>
                    <button type="submit" class="btn" style="background: #4285f4;">Özel Notu Gönder</button>
                </form>
            </div>
        <?php endforeach; ?>
    </div>
</body>
</html>