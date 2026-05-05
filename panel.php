<?php
session_start();
include 'baglan.php';

// Güvenlik Kontrolü: Giriş yapmamış veya rolü danışan olmayanları at
if (!isset($_SESSION['user_id']) || ($_SESSION['rol'] != 'danışan' && $_SESSION['rol'] != 'danisan')) {
    header("Location: index.php"); 
    exit();
}

$user_id = $_SESSION['user_id'];
$bugun = date('Y-m-d');

// 1. VERİLERİ ÇEK (İstatistik Kartları İçin)
$sorgu = $conn->prepare("SELECT SUM(su_miktari) as t_su, SUM(alinan_kalori) as t_alinan, SUM(uyku_suresi) as t_uyku FROM aktivite_kayitlari WHERE user_id = ? AND kayit_tarihi = ?");
$sorgu->execute([$user_id, $bugun]);
$veri = $sorgu->fetch(PDO::FETCH_ASSOC);

$su     = $veri['t_su'] ?? 0;
$alinan = $veri['t_alinan'] ?? 0;
$uyku   = $veri['t_uyku'] ?? 0;

// 2. DİYETİSYENİN GÜNÜN TARİFİNİ ÇEK
$tarif_sorgu = $conn->prepare("
    SELECT t.*, k.ad_soyad 
    FROM gunun_tarifi t 
    JOIN kullanicilar k ON t.diyetisyen_id = k.id 
    ORDER BY t.id DESC LIMIT 1
");
$tarif_sorgu->execute();
$gunun_tarifi = $tarif_sorgu->fetch(PDO::FETCH_ASSOC);

// 3. OKUNMAMIŞ MESAJ/PLAN UYARILARI
$d_uyari = $conn->prepare("SELECT id FROM beslenme_planlari WHERE user_id = ? AND okundu = 0 LIMIT 1");
$d_uyari->execute([$user_id]);
$yeni_diyet = $d_uyari->fetch();

$h_uyari = $conn->prepare("SELECT id FROM egzersiz_planlari WHERE user_id = ? AND okundu = 0 LIMIT 1");
$h_uyari->execute([$user_id]);
$yeni_hoca = $h_uyari->fetch();

// Bugün kayıt var mı kontrol et (Formu göstermek/gizlemek için)
$kontrol = $conn->prepare("SELECT id FROM aktivite_kayitlari WHERE user_id = ? AND kayit_tarihi = ?");
$kontrol->execute([$user_id, $bugun]);
$mevcut_kayit = $kontrol->fetch();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Sağlık Takip | Panel</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --blue: #0ea5e9; --orange: #f59e0b; --green: #10b981; --bg: #f8fafc; --sidebar: #ffffff; }
        body { font-family: 'Poppins', sans-serif; background: var(--bg); margin: 0; display: flex; color: #1e293b; }
        
        /* Sidebar */
        .sidebar { width: 260px; background: var(--sidebar); height: 100vh; padding: 30px 20px; box-shadow: 4px 0 24px rgba(0,0,0,0.03); position: fixed; }
        .logo { font-size: 22px; font-weight: 600; color: #0f172a; margin-bottom: 40px; display: flex; align-items: center; gap: 10px; }
        .menu-item { display: flex; align-items: center; padding: 14px 18px; color: #64748b; text-decoration: none; border-radius: 12px; margin-bottom: 8px; transition: 0.2s; }
        .menu-item.active { background: #f0f9ff; color: var(--blue); font-weight: 600; }
        .menu-item:hover:not(.active) { background: #f1f5f9; }

        /* Main Content */
        .main { margin-left: 300px; padding: 40px; width: calc(100% - 300px); }
        h1 { font-size: 28px; margin-bottom: 30px; }

        /* Günün Tarifi Kartı (Özel Tasarım) */
        .recipe-highlight {
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            border: 1px solid #bbf7d0;
            border-radius: 24px;
            padding: 25px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .recipe-content h3 { color: #166534; margin: 0 0 10px 0; }
        .recipe-content p { color: #374151; font-size: 14px; margin-bottom: 10px; }
        .recipe-badge { background: #10b981; color: white; padding: 4px 10px; border-radius: 8px; font-size: 12px; font-weight: 600; }

        /* Bildirim Kutusu */
        .msg-box { display: flex; align-items: center; justify-content: space-between; padding: 16px 24px; border-radius: 16px; margin-bottom: 20px; border: 1px solid; }
        
        /* Üst Kartlar */
        .stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; margin-bottom: 40px; }
        .stat-card { background: white; padding: 24px; border-radius: 24px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.04); border-left: 6px solid; }
        .stat-card.su { border-color: var(--blue); }
        .stat-card.kalori { border-color: var(--orange); }
        .stat-card.uyku { border-color: var(--green); }
        
        .stat-label { font-size: 14px; color: #64748b; display: flex; align-items: center; gap: 6px; margin-bottom: 8px; }
        .stat-value { font-size: 24px; font-weight: 600; margin-bottom: 15px; }
        
        .progress-container { background: #f1f5f9; height: 10px; border-radius: 99px; overflow: hidden; }
        .progress-bar { height: 100%; border-radius: 99px; transition: width 0.5s ease-out; }

        /* Alt Grid Alanı */
        .grid-container { display: grid; grid-template-columns: 1.4fr 1fr; gap: 30px; }
        .card { background: white; padding: 30px; border-radius: 24px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.04); }
        h3 { margin-top: 0; margin-bottom: 25px; font-size: 18px; display: flex; align-items: center; gap: 10px; }

        /* Vücut Haritası */
        .body-container { background: white; padding: 25px; border-radius: 24px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.04); text-align: center; }
        .body-wrapper { position: relative; width: 120px; height: 220px; margin: 0 auto; background: #f1f5f9; clip-path: path('M60,10 C70,10 75,18 75,25 C75,32 70,40 60,40 C50,40 45,32 45,25 C45,18 50,10 60,10 M40,45 L80,45 C90,45 95,55 95,65 L95,120 C95,130 85,130 85,120 L85,80 L75,80 L75,210 C75,220 65,220 65,210 L65,140 L55,140 L55,210 C55,220 45,220 45,210 L45,80 L35,80 L35,120 C35,130 25,130 25,120 L25,65 C25,55 30,45 40,45 Z'); overflow: hidden; }
        .water-fill { position: absolute; bottom: 0; width: 100%; background: linear-gradient(to top, #0ea5e9, #38bdf8); transition: height 1s ease-in-out; }
        
        /* Form ve Liste */
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px; }
        input { padding: 14px; border: 1px solid #e2e8f0; border-radius: 12px; background: #f8fafc; font-family: inherit; }
        .btn-submit { width: 100%; padding: 16px; background: var(--blue); color: white; border: none; border-radius: 14px; font-weight: 600; cursor: pointer; }
        .record-item { padding: 15px; border-bottom: 1px solid #f1f5f9; margin-bottom: 10px; }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="logo">🩺 Sağlık Takip</div>
    <a href="panel.php" class="menu-item active"><i class="fas fa-home" style="margin-right:10px;"></i> Özet Paneli</a>
    <a href="beslenme.php" class="menu-item"><i class="fas fa-apple-alt" style="margin-right:10px;"></i> Beslenme</a>
    <a href="egzersiz.php" class="menu-item"><i class="fas fa-dumbbell" style="margin-right:10px;"></i> Egzersiz</a>
    <a href="cikis.php" class="menu-item" style="color:#ef4444; margin-top: 40px;"><i class="fas fa-sign-out-alt" style="margin-right:10px;"></i> Çıkış Yap</a>
</div>

<div class="main">
    <h1>Hoş Geldin, <?php echo $_SESSION['ad_soyad']; ?>! 👋</h1>

    <?php if ($gunun_tarifi): ?>
        <div class="recipe-highlight">
            <div class="recipe-content">
                <span class="recipe-badge">🥗 Günün Sağlıklı Tarifi</span>
                <h3><?php echo htmlspecialchars($gunun_tarifi['baslik']); ?></h3>
                <p><?php echo nl2br(htmlspecialchars($gunun_tarifi['icerik'])); ?></p>
                <small style="color: #15803d; font-weight: 600;">👨‍⚕️ Diyetisyen: <?php echo $gunun_tarifi['ad_soyad']; ?></small>
            </div>
            <i class="fas fa-leaf" style="font-size: 60px; color: #10b981; opacity: 0.2;"></i>
        </div>
    <?php endif; ?>

    <?php if ($yeni_diyet): ?>
        <div class="msg-box" style="background:#fff9db; border-color:#fab005; color:#856404;">
            <span>🍎 Diyetisyeniniz yeni bir plan gönderdi!</span>
            <a href="beslenme.php" style="background:#f08c00; color:white; padding:6px 12px; border-radius:8px; text-decoration:none; font-size:12px;">Bak</a>
        </div>
    <?php endif; ?>

    <div class="stats">
        <div class="stat-card su">
            <div class="stat-label">💧 Bugün İçilen Su</div>
            <div class="stat-value"><?php echo $su; ?> / 2.5 L</div>
            <div class="progress-container">
                <div class="progress-bar" style="background: var(--blue); width: <?php echo min(($su/2.5)*100, 100); ?>%"></div>
            </div>
        </div>

        <div class="stat-card kalori">
            <div class="stat-label">🔥 Alınan Kalori</div>
            <div class="stat-value"><?php echo round($alinan); ?> / 2000 kcal</div>
            <div class="progress-container">
                <div class="progress-bar" style="background: var(--orange); width: <?php echo min(($alinan/2000)*100, 100); ?>%"></div>
            </div>
        </div>

        <div class="stat-card uyku">
            <div class="stat-label">😴 Uyku Süresi</div>
            <div class="stat-value"><?php echo $uyku; ?> / 8 Saat</div>
            <div class="progress-container">
                <div class="progress-bar" style="background: var(--green); width: <?php echo min(($uyku/8)*100, 100); ?>%"></div>
            </div>
        </div>
    </div>

    <div class="grid-container">
        <div class="card">
            <h3>➕ Bugünün Verilerini Gir</h3>
            <?php if ($mevcut_kayit): ?>
                <div style="background:#f1f5f9; padding:20px; border-radius:12px; text-align:center;">
                    <p>Bugün için kayıt başarıyla oluşturuldu. ✅</p>
                </div>
            <?php else: ?>
                <form action="islem_v2.php?is=verileri_kaydet" method="POST">
                    <div class="form-grid">
                        <input type="number" step="0.1" name="su_miktari" placeholder="Su (Litre)">
                        <input type="number" step="0.1" name="uyku_suresi" placeholder="Uyku (Saat)">
                        <input type="number" name="alinan_kalori" placeholder="Alınan Kalori">
                        <input type="number" name="yakilan_kalori" placeholder="Yakılan Kalori">
                    </div>
                    <button type="submit" class="btn-submit">Kaydı Sisteme İşle</button>
                </form>
            <?php endif; ?>
        </div>
        
        <div class="body-container">
            <h3>🧍 Vücut Durumum</h3>
            <div class="body-wrapper">
                <div class="water-fill" style="height: <?php echo min(($su / 2.5) * 100, 100); ?>%;"></div>
            </div>
            <div style="margin-top: 15px; font-size: 13px;">
                <p>💧 Hidrasyon: <b>%<?php echo round(min(($su / 2.5) * 100, 100)); ?></b></p>
                <p>🔋 Enerji: <b>%<?php echo round(min(($uyku / 8) * 100, 100)); ?></b></p>
            </div>
        </div>
    </div>
</div>

</body>
</html>