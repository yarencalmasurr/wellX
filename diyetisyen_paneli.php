<?php
/**
 * Proje: saglik_portali
 * Dosya: diyetisyen_paneli.php
 */

session_start();
include 'baglan.php'; // Veritabanı bağlantısı[cite: 24]

// GÜVENLİK KONTROLÜ: Giriş yapmamış veya rolü diyetisyen olmayanları engelle
if (!isset($_SESSION['user_id']) || ($_SESSION['rol'] != 'diyetisyen')) {
    header("Location: index.php"); 
    exit();
}

$diyetisyen_id = $_SESSION['user_id'];
$bugun = date('Y-m-d');

try {
    // HATA DÜZELTİLDİ: k.eposta olan yer k.email yapıldı
    // Diyetisyene bağlı danışanları ve bugünkü kalori durumlarını çeker
    $sorgu = $conn->prepare("
        SELECT k.id, k.ad_soyad, k.email, 
        (SELECT SUM(alinan_kalori) FROM aktivite_kayitlari WHERE user_id = k.id AND kayit_tarihi = ?) as bugunku_kalori 
        FROM kullanicilar k 
        WHERE k.rol = 'danisan' AND k.diyetisyen_id = ?
    ");
    $sorgu->execute([$bugun, $diyetisyen_id]);
    $danisanlar = $sorgu->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { 
    die("Veritabanı hatası: " . $e->getMessage()); 
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Diyetisyen Yönetim Paneli</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --primary: #1a3a3a; --accent: #34a853; --bg: #f0f7f4; --text: #2c3e50; }
        body { font-family: 'Poppins', sans-serif; background: var(--bg); margin: 0; display: flex; color: var(--text); }
        
        /* Sidebar */
        .sidebar { width: 260px; background: var(--primary); height: 100vh; color: white; padding: 30px; position: fixed; }
        .sidebar h2 { font-size: 22px; margin-bottom: 30px; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 15px; }
        .sidebar a { display: block; color: #ff9999; text-decoration: none; margin-top: 40px; font-weight: 600; }

        /* Main Content */
        .main { margin-left: 320px; padding: 40px; width: calc(100% - 320px); }
        
        /* Kartlar */
        .card { background: white; padding: 25px; border-radius: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.03); margin-bottom: 25px; border: 1px solid #e1e8e5; }
        .tarif-box { background: #e6f4ea; border: 2px dashed var(--accent); }
        
        /* Form Elemanları */
        input, textarea { width: 100%; padding: 12px; margin: 10px 0; border-radius: 10px; border: 1px solid #ccd6d1; box-sizing: border-box; font-family: inherit; }
        .btn { background: var(--accent); color: white; border: none; padding: 14px; border-radius: 10px; cursor: pointer; width: 100%; font-weight: 600; transition: 0.3s; }
        .btn:hover { background: #2d8e47; transform: translateY(-2px); }
        
        /* Uyarılar */
        .alert-success { background: #d1fae5; color: #065f46; padding: 15px; border-radius: 12px; margin-bottom: 20px; font-weight: 600; }
        .limit-warn { background: #ea4335; color: white; padding: 3px 10px; border-radius: 20px; font-size: 11px; margin-left: 10px; }
    </style>
</head>
<body>

<div class="sidebar">
    <h2>🍎 Diyetisyen Paneli</h2>
    <p>Hoş Geldiniz,<br><strong><?php echo htmlspecialchars($_SESSION['ad_soyad']); ?></strong></p>
    <a href="cikis.php"><i class="fas fa-sign-out-alt"></i> Güvenli Çıkış</a>
</div>

<div class="main">
    <!-- İşlem Durum Bildirimleri[cite: 31] -->
    <?php if(isset($_GET['durum'])): ?>
        <?php if($_GET['durum'] == 'tarif_ok'): ?>
            <div class="alert-success">✅ Tarif başarıyla yayınlandı ve tüm danışanlara iletildi.</div>
        <?php elseif($_GET['durum'] == 'mesaj_gonderildi'): ?>
            <div class="alert-success">✅ Danışana özel not başarıyla gönderildi.</div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- Tarif Paylaşma Alanı[cite: 31, 41] -->
    <div class="card tarif-box">
        <h3><i class="fas fa-utensils"></i> Günün Tarifini / Duyurusunu Paylaş</h3>
        <form action="islem_v2.php?is=tarif_kaydet" method="POST">
            <input type="text" name="tarif_baslik" placeholder="Tarif Başlığı (Örn: Detoks Suyu)" required>
            <textarea name="tarif_icerik" rows="4" placeholder="Malzemeler ve hazırlanış..." required></textarea>
            <button type="submit" class="btn">Tarifi Tüm Danışanlara Gönder</button>
        </form>
    </div>

    <h2><i class="fas fa-users"></i> Aktif Danışan Takibi</h2>

    <?php if(empty($danisanlar)): ?>
        <p>Henüz size atanmış bir danışan bulunmuyor.</p>
    <?php else: ?>
        <?php foreach($danisanlar as $d): ?>
            <div class="card">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <strong><?php echo htmlspecialchars($d['ad_soyad']); ?></strong>
                    <span style="font-size: 12px; color: #7f8c8d;"><?php echo htmlspecialchars($d['email']); ?></span>
                </div>
                
                <p>
                    Bugün Alınan Kalori: <strong><?php echo $d['bugunku_kalori'] ?? 0; ?> kcal</strong>
                    <?php if(($d['bugunku_kalori'] ?? 0) > 2200): ?>
                        <span class="limit-warn">⚠️ Sınır Aşıldı</span>
                    <?php endif; ?>
                </p>

                <!-- Özel Not Gönderme Formu[cite: 31, 41] -->
                <form action="islem_v2.php?is=plan_yaz" method="POST">
                    <input type="hidden" name="danisan_id" value="<?php echo $d['id']; ?>">
                    <textarea name="plan_metni" placeholder="<?php echo htmlspecialchars($d['ad_soyad']); ?> için notunuzu yazın..."></textarea>
                    <button type="submit" class="btn" style="background: #4285f4;">Özel Not Gönder</button>
                </form>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

</body>
</html>