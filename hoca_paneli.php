<?php
/**
 * Proje: saglik_portali
 * Dosya: hoca_paneli.php
 * Açıklama: Spor hocasının sadece kendine bağlı danışanları yönettiği panel
 */

session_start();
include 'baglan.php';

// Güvenlik Kontrolü: Giriş yapmamış veya rolü hoca olmayanları engelle
if (!isset($_SESSION['user_id']) || ($_SESSION['rol'] != 'hoca')) {
    header("Location: index.php"); 
    exit();
}

$hoca_id = $_SESSION['user_id'];
$bugun = date('Y-m-d');

try {
    /** * GÜNCEL SORGU: 
     * Artık sadece 'kullanicilar' tablosuna bakmıyoruz. 
     * 'uzman_danisan_eslesmeleri' tablosu üzerinden SADECE bu hocaya bağlı danışanları çekiyoruz.
     */
    $sorgu = $conn->prepare("
        SELECT k.id, k.ad_soyad, k.email,
        (SELECT SUM(spor_suresi) FROM aktivite_kayitlari WHERE user_id = k.id AND kayit_tarihi = ?) as bugunku_spor
        FROM kullanicilar k 
        JOIN uzman_danisan_eslesmeleri ude ON k.id = ude.danisan_id
        WHERE ude.uzman_id = ? AND ude.uzman_rol = 'hoca'
    ");
    $sorgu->execute([$bugun, $hoca_id]);
    $danisanlar = $sorgu->fetchAll(PDO::FETCH_ASSOC);

    // Hocanın paylaştığı son duyuruyu/antrenmanı çekiyoruz
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
        
        .sidebar { width: 260px; background: var(--primary); height: 100vh; color: white; padding: 30px; position: fixed; box-shadow: 4px 0 15px rgba(0,0,0,0.1); }
        .sidebar h2 { font-size: 20px; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 15px; }
        .sidebar a { display: block; color: white; text-decoration: none; margin-top: 20px; padding: 10px; border-radius: 8px; transition: 0.3s; }
        .sidebar a:hover { background: rgba(255,255,255,0.1); }

        .main { margin-left: 320px; padding: 40px; width: calc(100% - 320px); }
        h1 { font-size: 26px; margin-bottom: 30px; }

        .card { background: white; padding: 25px; border-radius: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.03); margin-bottom: 25px; border: 1px solid #eef2f0; }
        .card h3 { margin-top: 0; display: flex; align-items: center; gap: 10px; font-size: 18px; }
        
        .alert { background: #ffeaa7; color: #d35400; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; text-transform: uppercase; }
        
        textarea { width: 100%; height: 80px; margin: 10px 0; border-radius: 10px; border: 1px solid #ddd; padding: 10px; resize: none; box-sizing: border-box; font-family: inherit; }
        .btn { background: var(--accent); color: white; border: none; padding: 12px 25px; border-radius: 10px; cursor: pointer; font-weight: 600; width: 100%; transition: 0.3s; }
        .btn:hover { background: #2980b9; transform: translateY(-2px); }
        .tarif-box { background: #ebf5fb; border: 1px dashed var(--accent); }
        .son-kayit { background: #f8fafc; padding: 15px; border-radius: 10px; margin-top: 10px; border-left: 4px solid var(--accent); font-size: 14px; }
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
    <?php if(isset($_GET['paylasim']) && $_GET['paylasim'] == 'basarili'): ?>
        <div class="alert" style="background:#d1fae5; color:#065f46; margin-bottom:20px; padding:15px; text-transform:none; font-size:14px;">
            ✅ Duyuru başarıyla yayınlandı.
        </div>
    <?php endif; ?>

    <div class="card tarif-box">
        <h3>📢 Günün Antrenman Duyurusunu Paylaş</h3>
        <form action="islem_v2.php?is=antrenman_paylas" method="POST">
            <input type="text" name="antrenman_baslik" placeholder="Başlık (Örn: Kardiyo Günü)" required style="width:100%; padding:10px; border-radius:8px; border:1px solid #ddd; margin-bottom:10px; box-sizing:border-box;">
            <textarea name="antrenman_icerik" placeholder="Tüm sporcularınızın göreceği genel not..." required></textarea>
            <button type="submit" class="btn" style="background:var(--primary);">Duyuruyu Yayınla</button>
        </form>

        <?php if($son_duyuru): ?>
            <div class="son-kayit">
                <strong>Paylaşılan Son Duyuru:</strong><br>
                <em><?php echo htmlspecialchars($son_duyuru['antrenman_baslik']); ?></em>: 
                <?php echo htmlspecialchars($son_duyuru['antrenman_icerik']); ?>
            </div>
        <?php endif; ?>
    </div>

    <h1>Size Bağlı Sporcular</h1>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
        <?php if($danisanlar): ?>
            <?php foreach($danisanlar as $d): ?>
                <div class="card">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <h3>👤 <?php echo htmlspecialchars($d['ad_soyad']); ?></h3>
                        <span style="font-size: 12px; color: #94a3b8;"><?php echo htmlspecialchars($d['email']); ?></span>
                    </div>
                    
                    <p>Bugünkü Spor: <strong><?php echo $d['bugunku_spor'] ?? 0; ?> dk</strong> 
                    <?php if(($d['bugunku_spor'] ?? 0) < 30): ?>
                        <span class="alert">⚠️ Hedef Altı</span>
                    <?php endif; ?>
                    </p>

                    <?php 
                        $not_sorgu = $conn->prepare("SELECT antrenman_notu FROM egzersiz_planlari WHERE user_id = ? AND hoca_id = ? ORDER BY id DESC LIMIT 1");
                        $not_sorgu->execute([$d['id'], $hoca_id]);
                        $son_not = $not_sorgu->fetch();
                        if($son_not):
                    ?>
                        <div class="son-kayit" style="border-left-color: #2ecc71; margin-bottom: 10px;">
                            <strong>Gönderilen Son Program:</strong><br>
                            <?php echo htmlspecialchars($son_not['antrenman_notu']); ?>
                        </div>
                    <?php endif; ?>
                    
                    <form action="islem_v2.php?is=egzersiz_yaz" method="POST">
                        <input type="hidden" name="danisan_id" value="<?php echo $d['id']; ?>">
                        <textarea name="plan_metni" placeholder="<?php echo $d['ad_soyad']; ?> için özel antrenman programı..." required></textarea>
                        <button type="submit" class="btn">Notu Gönder</button>
                    </form>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="card" style="grid-column: span 2; text-align: center; padding: 30px;">
                <p style="color: #94a3b8;">Henüz size atanmış bir sporcu bulunmuyor.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>