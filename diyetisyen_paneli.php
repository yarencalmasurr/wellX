<?php
/**
 * Proje: saglik_portali
 * Dosya: diyetisyen_paneli.php
 */

session_start();
include 'baglan.php'; 

// GÜVENLİK KONTROLÜ
if (!isset($_SESSION['user_id']) || ($_SESSION['rol'] != 'diyetisyen')) {
    header("Location: index.php"); 
    exit();
}

$diyetisyen_id = $_SESSION['user_id'];
$bugun = date('Y-m-d');

try {
    /** * GÜNCEL SORGU: Aktif Danışanlar */
    $sorgu = $conn->prepare("
        SELECT k.id, k.ad_soyad, k.email, 
        (SELECT SUM(alinan_kalori) FROM aktivite_kayitlari WHERE user_id = k.id AND kayit_tarihi = ?) as bugunku_kalori 
        FROM kullanicilar k 
        JOIN uzman_danisan_eslesmeleri ude ON k.id = ude.danisan_id
        WHERE ude.uzman_id = ? AND ude.uzman_rol = 'diyetisyen'
    ");
    $sorgu->execute([$bugun, $diyetisyen_id]);
    $danisanlar = $sorgu->fetchAll(PDO::FETCH_ASSOC);

    /**
     * BEKLEYEN SORULAR SORGUSU
     * Tablo yapına uygun olarak 'beklemede' durumundaki soruları çekiyoruz.
     */
    $soru_sorgu = $conn->prepare("
        SELECT s.*, k.ad_soyad as danisan_adi 
        FROM uzman_sorulari s 
        INNER JOIN kullanicilar k ON s.danisan_id = k.id 
        WHERE s.uzman_id = ? AND s.durum = 'beklemede'
        ORDER BY s.soru_tarihi DESC
    ");
    $soru_sorgu->execute([$diyetisyen_id]);
    $gelen_sorular = $soru_sorgu->fetchAll(PDO::FETCH_ASSOC);

    /**
     * CEVAPLANMIŞ SORULAR SORGUSU (Ekranın altında görünmesi için)
     */
    $cevaplanan_sorgu = $conn->prepare("
        SELECT s.*, k.ad_soyad as danisan_adi 
        FROM uzman_sorulari s 
        INNER JOIN kullanicilar k ON s.danisan_id = k.id 
        WHERE s.uzman_id = ? AND s.durum = 'cevaplandi'
        ORDER BY s.soru_tarihi DESC LIMIT 5
    ");
    $cevaplanan_sorgu->execute([$diyetisyen_id]);
    $cevaplanan_sorular = $cevaplanan_sorgu->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Hata: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Diyetisyen Paneli</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #2ecc71; --bg: #f8fafc; }
        body { font-family: 'Inter', sans-serif; background: var(--bg); margin: 0; padding: 40px; }
        .container { max-width: 1100px; margin: auto; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .card { background: white; padding: 20px; border-radius: 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin-bottom: 20px; }
        .btn { border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; color: white; font-weight: 600; transition: 0.3s; }
        textarea { width: 100%; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px; margin-top: 10px; font-family: inherit; resize: vertical; }
        .limit-warn { color: #e74c3c; font-size: 12px; font-weight: 600; background: #fdedec; padding: 2px 8px; border-radius: 10px; }
        .question-badge { background: #eef2ff; color: #4f46e5; padding: 4px 12px; border-radius: 20px; font-size: 13px; font-weight: 600; }
        .empty-state { text-align: center; padding: 40px; color: #94a3b8; }
        .answered-card { opacity: 0.8; background: #fcfdfd; border-left: 5px solid #2ecc71; }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1>🍎 Diyetisyen Yönetim Paneli</h1>
        <div>
            <strong>Hoş geldin, <?php echo htmlspecialchars($_SESSION['ad_soyad']); ?></strong>
            <a href="cikis.php" style="margin-left:20px; color:#e74c3c; text-decoration:none;">Çıkış Yap</a>
        </div>
    </div>

    <?php if (count($gelen_sorular) == 0): ?>
        <?php endif; ?>

    <h2 style="margin-top: 40px;">📩 Cevaplanacak Sorular</h2>
    <?php if (count($gelen_sorular) == 0): ?>
        <div class="card empty-state">Henüz beklemede olan bir soru bulunmuyor.</div>
    <?php else: ?>
        <?php foreach($gelen_sorular as $s): ?>
            <div class="card" style="border-left: 5px solid #4f46e5;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span class="question-badge">Danışan: <?php echo htmlspecialchars($s['danisan_adi']); ?></span>
                    <small style="color: #94a3b8;"><?php echo date('d.m.Y H:i', strtotime($s['soru_tarihi'])); ?></small>
                </div>
                <p style="margin: 15px 0; font-style: italic; color: #475569;">"<?php echo htmlspecialchars($s['soru_metni']); ?>"</p>
                
                <form action="islem_v2.php?is=cevapla" method="POST">
                    <input type="hidden" name="soru_id" value="<?php echo $s['id']; ?>">
                    <textarea name="cevap_metni" placeholder="Cevabınızı buraya yazın..." required></textarea>
                    <button type="submit" class="btn" style="background: #4f46e5; margin-top: 10px;">Cevabı Gönder</button>
                </form>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php if (count($cevaplanan_sorular) > 0): ?>
        <h2 style="margin-top: 40px; color: #64748b; font-size: 1.2rem;">✅ Son Cevapladıklarım</h2>
        <?php foreach($cevaplanan_sorular as $cs): ?>
            <div class="card answered-card">
                <div style="font-size: 0.85rem; color: #64748b;">
                    <strong><?php echo htmlspecialchars($cs['danisan_adi']); ?></strong> sordu: 
                    <span style="font-style: italic;">"<?php echo htmlspecialchars($cs['soru_metni']); ?>"</span>
                </div>
                <div style="margin-top: 10px; color: #166534; font-size: 0.9rem;">
                    <strong>Cevabınız:</strong> <?php echo htmlspecialchars($cs['cevap_metni']); ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <h2 style="margin-top: 40px;">👥 Aktif Danışanlarım</h2>
    <?php if (count($danisanlar) == 0): ?>
        <div class="card empty-state">
            <p>Henüz size atanmış bir danışan bulunmuyor.</p>
        </div>
    <?php else: ?>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <?php foreach($danisanlar as $d): ?>
                <div class="card">
                    <div style="display: flex; justify-content: space-between;">
                        <strong><?php echo htmlspecialchars($d['ad_soyad']); ?></strong>
                        <small><?php echo htmlspecialchars($d['email']); ?></small>
                    </div>
                    
                    <p style="margin: 15px 0;">
                        Bugünkü Kalori: <strong><?php echo $d['bugunku_kalori'] ?? 0; ?></strong>
                        <?php if(($d['bugunku_kalori'] ?? 0) > 2200): ?>
                            <span class="limit-warn">Sınır Aşıldı!</span>
                        <?php endif; ?>
                    </p>

                    <form action="islem_v2.php?is=plan_yaz" method="POST">
                        <input type="hidden" name="danisan_id" value="<?php echo $d['id']; ?>">
                        <textarea name="plan_metni" placeholder="Beslenme planı notlarını girin..." style="height: 100px;"></textarea>
                        <button type="submit" class="btn" style="background: #2ecc71; margin-top: 10px;">Planı Gönder</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

</body>
</html>