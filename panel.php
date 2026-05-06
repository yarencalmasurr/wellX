<?php
/**
 * Proje: saglik_portali
 * Dosya: panel.php
 * Açıklama: Danışanların günlük veri girişi ve takip paneli
 */

// 1. Oturumu başlat
session_start(); 

// 2. Veritabanı bağlantısını dahil et
include 'baglan.php'; 
$user_id = $_SESSION['user_id'];

$sorgu = $conn->prepare("SELECT * FROM kullanicilar WHERE id = ?");
$sorgu->execute([$user_id]);
$user_data = $sorgu->fetch(PDO::FETCH_ASSOC);

// Eğer kullanıcı bulunamazsa giriş sayfasına yönlendir (Güvenlik için)
if (!$user_data) {
    header("Location: giris.php");
    exit();
}
// 3. Güvenlik Kontrolü: Kullanıcı giriş yapmamışsa veya danışan değilse index'e gönder
if (!isset($_SESSION['user_id']) || ($_SESSION['rol'] != 'danışan' && $_SESSION['rol'] != 'danisan')) {
    header("Location: index.php"); 
    exit();
}

// Kullanıcı bilgilerini ve tarih bilgisini al
$user_id = $_SESSION['user_id'];
$bugun = date('Y-m-d');

// Premium Durumu Kontrolü (Veritabanında is_premium sütunu olduğunu varsayıyoruz)
$kullanici_sorgu = $conn->prepare("SELECT is_premium FROM kullanicilar WHERE id = ?");
$kullanici_sorgu->execute([$user_id]);
$kullanici_veri = $kullanici_sorgu->fetch();
$is_premium = $kullanici_veri['is_premium'] ?? 0;

// --- GÜNÜN TARİFİ SORGUSU ---
$tarif_sorgu = $conn->prepare("
    SELECT t.*, k.ad_soyad 
    FROM gunun_tarifi t 
    JOIN kullanicilar k ON t.diyetisyen_id = k.id 
    ORDER BY t.id DESC LIMIT 1
");
$tarif_sorgu->execute();
$gunun_tarifi = $tarif_sorgu->fetch(PDO::FETCH_ASSOC);

// --- YENİ: DANIŞANIN BU TARİFE VERDİĞİ PUANI ÇEK ---
$mevcut_puan = 0;
if ($gunun_tarifi) {
    $puan_cek = $conn->prepare("SELECT puan FROM tarif_puanlari WHERE tarif_id = ? AND user_id = ?");
    $puan_cek->execute([$gunun_tarifi['id'], $user_id]);
    $puan_veri = $puan_cek->fetch();
    if ($puan_veri) {
        $mevcut_puan = $puan_veri['puan'];
    }
}

// --- GÜNLÜK KAYIT SINIRI KONTROLÜ ---
$kontrol = $conn->prepare("SELECT id FROM aktivite_kayitlari WHERE user_id = ? AND kayit_tarihi = ?");
$kontrol->execute([$user_id, $bugun]);
$mevcut_kayit = $kontrol->fetch();

// İstatistikleri çek (Bugünün verileri)
$sorgu = $conn->prepare("SELECT SUM(su_miktari) as t_su, SUM(alinan_kalori) as t_alinan, SUM(uyku_suresi) as t_uyku FROM aktivite_kayitlari WHERE user_id = ? AND kayit_tarihi = ?");
$sorgu->execute([$user_id, $bugun]);
$veri = $sorgu->fetch(PDO::FETCH_ASSOC);

$su = $veri['t_su'] ?? 0;
$alinan = $veri['t_alinan'] ?? 0;
$uyku = $veri['t_uyku'] ?? 0;

// Bildirimleri kontrol et
$d_uyari = $conn->prepare("SELECT id FROM beslenme_planlari WHERE user_id = ? AND okundu = 0 LIMIT 1");
$d_uyari->execute([$user_id]);
$yeni_diyet = $d_uyari->fetch();

$h_uyari = $conn->prepare("SELECT id FROM egzersiz_planlari WHERE user_id = ? AND okundu = 0 LIMIT 1");
$h_uyari->execute([$user_id]);
$yeni_hoca = $h_uyari->fetch();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Sağlık Takip | Panel</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        :root { --blue: #0ea5e9; --orange: #f59e0b; --green: #10b981; --bg: #f8fafc; --sidebar: #ffffff; }
        body { font-family: 'Poppins', sans-serif; background: var(--bg); margin: 0; display: flex; color: #1e293b; }
        .sidebar { width: 260px; background: var(--sidebar); height: 100vh; padding: 30px 20px; box-shadow: 4px 0 24px rgba(0,0,0,0.03); position: fixed; z-index: 100;}
        .logo { font-size: 22px; font-weight: 600; color: #0f172a; margin-bottom: 40px; display: flex; align-items: center; gap: 10px; text-decoration:none; }
        .menu-item { display: flex; align-items: center; padding: 14px 18px; color: #64748b; text-decoration: none; border-radius: 12px; margin-bottom: 8px; transition: 0.2s; }
        .menu-item.active { background: #f0f9ff; color: var(--blue); font-weight: 600; }
        .main { margin-left: 260px; padding: 40px; width: calc(100% - 260px); position: relative; }
        .recipe-highlight { background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border: 1px solid #bbf7d0; border-radius: 24px; padding: 25px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 24px; border-radius: 24px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.04); border-left: 6px solid; }
        .progress-container { background: #f1f5f9; height: 10px; border-radius: 99px; overflow: hidden; margin-top: 10px; }
        .progress-bar { height: 100%; border-radius: 99px; transition: width 0.5s; }
        .btn-submit { width: 100%; padding: 16px; background: var(--blue); color: white; border: none; border-radius: 14px; font-weight: 600; cursor: pointer; }
        
        /* Premium Buton Stili */
        .premium-btn {
            position: absolute;
            top: 40px;
            right: 40px;
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
            padding: 12px 24px;
            border-radius: 14px;
            font-weight: 600;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 10px 15px -3px rgba(245, 158, 11, 0.3);
            transition: 0.3s;
            border: none;
        }
        .premium-btn:hover { transform: translateY(-3px); color: white; box-shadow: 0 15px 20px -3px rgba(245, 158, 11, 0.4); }

        .rating-btn { background: white; border: 1px solid #ddd; padding: 8px 12px; border-radius: 10px; cursor: pointer; transition: 0.3s; font-family: inherit; }
        .rating-btn:hover { background: #f0f9ff; border-color: var(--blue); }
        .rating-btn.active { background: var(--blue); color: white; border-color: var(--blue); }
    </style>
</head>
<body>

<div class="sidebar">
    <a href="panel.php" class="logo">🩺 Sağlık Takip</a>
    <a href="panel.php" class="menu-item active">🏠 Özet Paneli</a>
    <a href="beslenme.php" class="menu-item">🥗 Beslenme</a>
    <a href="egzersiz.php" class="menu-item">🏋️ Egzersiz</a>
    <a href="gelisim.php" class="menu-item">📈 Gelişim</a>
    <a href="danisan_mesajlar.php" class="menu-item">📩 Uzman Notlarım</a>
    <a href="cikis.php" class="menu-item" style="color:#ef4444; margin-top: 40px;">🚪 Çıkış Yap</a>
</div>

<div class="main">

    <?php if(!$is_premium): ?>

    <button type="button" class="premium-btn" data-bs-toggle="modal" data-bs-target="#premiumInfoModal">
        <i class="fas fa-crown"></i> Premium Edinin
    </button>

    <?php else: ?>

    <div class="dropdown" style="position: absolute; top: 40px; right: 40px;">
        
        <button class="premium-btn dropdown-toggle" 
                type="button" 
                data-bs-toggle="dropdown" 
                aria-expanded="false"
                style="background: #10b981; border:none;">
            <i class="fas fa-check-circle"></i> Premium Üye
        </button>

        <ul class="dropdown-menu dropdown-menu-end" style="border-radius: 14px; padding: 10px; border:none; box-shadow: 0 10px 25px rgba(0,0,0,0.08);">
            <li>
                <a class="dropdown-item" 
                href="premium_yonet.php"
                onclick="return confirm('Premium üyeliğini iptal etmek istediğine emin misin?')"
                style="color:#ef4444; font-weight:600; border-radius:10px;">
                <i class="fas fa-times-circle"></i> Premiumu İptal Et
                </a>
            </li>
        </ul>

    </div>

    <?php endif; ?>
    

    <h1>Hoş Geldin, <?php echo htmlspecialchars($_SESSION['ad_soyad']); ?>! 👋</h1>

    <?php if ($gunun_tarifi): ?>
        <div class="recipe-highlight">
            <h3 style="color: #166534; margin: 0;"><i class="fas fa-utensils"></i> Günün Sağlıklı Tarifi</h3>
            <h4 style="margin: 10px 0;"><?php echo htmlspecialchars($gunun_tarifi['tarif_baslik']); ?></h4>
            <p style="font-size: 14px; color: #374151;"><?php echo nl2br(htmlspecialchars($gunun_tarifi['tarif_icerik'])); ?></p>
            <div style="display: flex; justify-content: space-between; align-items: flex-end;">
                <small style="color: #15803d; font-weight: 600;">👨‍⚕️ Diyetisyen: <?php echo htmlspecialchars($gunun_tarifi['ad_soyad']); ?></small>
                
                <div style="text-align: right;">
                    <p style="font-size: 12px; font-weight: 600; margin-bottom: 5px; color: #166534;">Bu tarife puan ver:</p>
                    <form action="islem_v2.php?is=puan_ver" method="POST" style="display: flex; gap: 5px;">
                        <input type="hidden" name="tarif_id" value="<?php echo $gunun_tarifi['id']; ?>">
                        <?php for($i=1; $i<=5; $i++): ?>
                            <button type="submit" name="puan" value="<?php echo $i; ?>" 
                                    class="rating-btn <?php echo ($mevcut_puan == $i) ? 'active' : ''; ?>">
                                <?php echo $i; ?> ⭐
                            </button>
                        <?php endfor; ?>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($yeni_diyet || $yeni_hoca || isset($_GET['puan'])): ?>
        <div style="background:#fff9db; border-color:#fab005; padding: 15px; border-radius: 16px; margin-bottom: 20px; border: 1px solid;">
            <?php if(isset($_GET['puan'])): ?>
                ✅ Puanınız başarıyla kaydedildi!
            <?php else: ?>
                🔔 Yeni bir uzman notunuz var! <a href="danisan_mesajlar.php" style="color: #856404; font-weight: 600;">Görüntüle</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; margin-bottom: 40px;">
        <div class="stat-card" style="border-color: var(--blue);">
            <div style="font-size: 14px; color: #64748b;">💧 Su</div>
            <div style="font-size: 24px; font-weight: 600;"><?php echo $su; ?> / 2.5 L</div>
            <div class="progress-container">
                <div class="progress-bar" style="background: var(--blue); width: <?php echo min(($su/2.5)*100, 100); ?>%"></div>
            </div>
        </div>
        <div class="stat-card" style="border-color: var(--orange);">
            <div style="font-size: 14px; color: #64748b;">🔥 Kalori</div>
            <div style="font-size: 24px; font-weight: 600;"><?php echo round($alinan); ?> / 2000</div>
            <div class="progress-container">
                <div class="progress-bar" style="background: var(--orange); width: <?php echo min(($alinan/2000)*100, 100); ?>%"></div>
            </div>
        </div>
        <div class="stat-card" style="border-color: var(--green);">
            <div style="font-size: 14px; color: #64748b;">😴 Uyku</div>
            <div style="font-size: 24px; font-weight: 600;"><?php echo $uyku; ?> / 8 Saat</div>
            <div class="progress-container">
                <div class="progress-bar" style="background: var(--green); width: <?php echo min(($uyku/8)*100, 100); ?>%"></div>
            </div>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 1.4fr 1fr; gap: 30px;">
        <div style="background: white; padding: 30px; border-radius: 24px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.04);">
            <h3>➕ Bugünün Verilerini Gir</h3>
            <?php if ($mevcut_kayit): ?>
                <div style="background:#fff7ed; padding:20px; border-radius:12px; text-align:center; border:1px solid #ffedd5;">
                    <p>⚠️ Bugün zaten kayıt yaptınız.</p>
                    <a href="guncelle.php" style="background:#f97316; color:white; padding:10px 20px; border-radius:8px; text-decoration:none; font-weight:600;">Güncelle</a>
                </div>
            <?php else: ?>
                <form action="islem_v2.php?is=verileri_kaydet" method="POST">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px;">
                        <input type="number" step="0.1" name="su_miktari" placeholder="Su (Litre)" required style="padding:14px; border:1px solid #e2e8f0; border-radius:12px;">
                        <input type="number" step="0.1" name="uyku_suresi" placeholder="Uyku (Saat)" required style="padding:14px; border:1px solid #e2e8f0; border-radius:12px;">
                        <input type="number" name="alinan_kalori" placeholder="Alınan Kalori" required style="padding:14px; border:1px solid #e2e8f0; border-radius:12px;">
                        <input type="number" name="yakilan_kalori" placeholder="Yakılan Kalori" required style="padding:14px; border:1px solid #e2e8f0; border-radius:12px;">
                        <input type="number" name="spor_suresi" placeholder="Spor (Dakika)" required style="padding:14px; border:1px solid #e2e8f0; border-radius:12px;">
                        <input type="number" step="0.1" name="guncel_kilo" placeholder="Kilo (kg)" required style="padding:14px; border:1px solid #e2e8f0; border-radius:12px;">
                    </div>
                    <button type="submit" class="btn-submit">Kaydı Sisteme İşle</button>
                </form>
            <?php endif; ?>
        </div>

        <div style="background: white; padding: 30px; border-radius: 24px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.04);">
            <h3>📋 Bugünün Kayıtları</h3>
            <?php
            $liste = $conn->prepare("SELECT * FROM aktivite_kayitlari WHERE user_id = ? AND kayit_tarihi = ? ORDER BY id DESC");
            $liste->execute([$user_id, $bugun]);
            $satirlar = $liste->fetchAll();

            foreach ($satirlar as $k):
            ?>
            <div style="padding: 15px; border-bottom: 1px solid #f1f5f9; background: #fcfcfc; border-radius: 10px; margin-bottom: 10px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
                    <span style="font-size: 13px; color: #64748b;">Kayıt Özeti</span>
                    <a href="islem_v2.php?is=kayit_sil&id=<?php echo $k['id']; ?>" onclick="return confirm('Silmek istediğine emin misin?')" style="color:#ef4444; font-size:13px; text-decoration:none;">Sil</a>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 5px; font-size: 13px;">
                    <div>🍎 <?php echo $k['alinan_kalori']; ?> kcal</div>
                    <div>💧 <?php echo $k['su_miktari']; ?> L</div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php if(!$satirlar): ?>
                <p style="text-align:center; color:#94a3b8;">Henüz kayıt yok.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="modal fade" id="premiumInfoModal" tabindex="-1" aria-labelledby="premiumInfoModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="border-radius: 30px; border: none; overflow: hidden;">
      <div class="modal-header" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; border: none; padding: 25px;">
        <h5 class="modal-title" id="premiumInfoModalLabel"><i class="fas fa-crown"></i> Neden Premium?</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" style="padding: 30px;">
        <ul class="list-unstyled">
            <li class="mb-3"><i class="fas fa-check-circle text-warning"></i> <b>Kişisel Uzman Desteği:</b> Diyetisyen ve Spor hocanızla birebir iletişim kurun.</li>
            <li class="mb-3"><i class="fas fa-check-circle text-warning"></i> <b>Özel Planlar:</b> Size özel hazırlanmış haftalık yemek ve egzersiz listeleri.</li>
            <li class="mb-3"><i class="fas fa-check-circle text-warning"></i> <b>Detaylı Analiz:</b> Gelişim grafiklerinizi aylık ve yıllık bazda görüntüleyin.</li>
            <li class="mb-3"><i class="fas fa-check-circle text-warning"></i> <b>Öncelikli Erişim:</b> Yeni tarifler ve özelliklerden ilk siz haberdar olun.</li>
        </ul>
        <hr>
        <div class="text-center mt-4">
            <a href="premium_planlar.php" class="btn btn-warning w-100" style="padding: 15px; border-radius: 15px; font-weight: 600; font-size: 18px;">Planları Gör ve Devam Et</a>
        </div>
      </div>
    </div>
  </div>
</div>
</body>
</html>

