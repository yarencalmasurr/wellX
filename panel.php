<?php
/**
 * Proje: saglik_portali
 * Dosya: panel.php
 * Açıklama: Danışanların ana yönetim paneli - Bildirim sistemli sadeleştirilmiş versiyon.
 */

session_start(); 
include 'baglan.php'; 

// Oturum kontrolü
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Kullanıcı Verilerini Çek
$sorgu = $conn->prepare("SELECT * FROM kullanicilar WHERE id = ?");
$sorgu->execute([$user_id]);
$user_data = $sorgu->fetch(PDO::FETCH_ASSOC);

// Güvenlik: Danışan değilse erişimi engelle
if (!$user_data || ($_SESSION['rol'] != 'danışan' && $_SESSION['rol'] != 'danisan')) {
    header("Location: index.php"); 
    exit();
}

$bugun = date('Y-m-d');
$is_premium = $user_data['is_premium'] ?? 0;

// --- UZMAN EŞLEŞME KONTROLLERİ ---
$diy_kontrol = $conn->prepare("SELECT uzman_id FROM uzman_danisan_eslesmeleri WHERE danisan_id = ? AND uzman_rol = 'diyetisyen'");
$diy_kontrol->execute([$user_id]);
$has_diyetisyen = $diy_kontrol->fetch();

$hoca_kontrol = $conn->prepare("SELECT uzman_id FROM uzman_danisan_eslesmeleri WHERE danisan_id = ? AND uzman_rol = 'hoca'");
$hoca_kontrol->execute([$user_id]);
$has_hoca = $hoca_kontrol->fetch();

// --- GÜNÜN TARİFİ ---
$tarif_sorgu = $conn->prepare("
    SELECT t.*, k.ad_soyad 
    FROM gunun_tarifi t 
    JOIN kullanicilar k ON t.diyetisyen_id = k.id 
    WHERE t.ekleme_tarihi >= DATE_SUB(NOW(), INTERVAL 1 DAY)
    ORDER BY t.id DESC LIMIT 1
");
$tarif_sorgu->execute();
$gunun_tarifi = $tarif_sorgu->fetch(PDO::FETCH_ASSOC);

// Tarife Verilen Puan
$mevcut_puan = 0;
if ($gunun_tarifi) {
    $puan_cek = $conn->prepare("SELECT puan FROM tarif_puanlari WHERE tarif_id = ? AND user_id = ?");
    $puan_cek->execute([$gunun_tarifi['id'], $user_id]);
    $puan_veri = $puan_cek->fetch();
    $mevcut_puan = $puan_veri['puan'] ?? 0;
}

// --- İSTATİSTİKLER ---
$stat_sorgu = $conn->prepare("SELECT SUM(su_miktari) as t_su, SUM(alinan_kalori) as t_alinan, SUM(uyku_suresi) as t_uyku FROM aktivite_kayitlari WHERE user_id = ? AND kayit_tarihi = ?");
$stat_sorgu->execute([$user_id, $bugun]);
$veri = $stat_sorgu->fetch(PDO::FETCH_ASSOC);

$su = $veri['t_su'] ?? 0;
$alinan = $veri['t_alinan'] ?? 0;
$uyku = $veri['t_uyku'] ?? 0;

// Kayıt Kontrolü
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        :root { --blue: #0ea5e9; --orange: #f59e0b; --green: #10b981; --bg: #f8fafc; --sidebar: #ffffff; }
        body { font-family: 'Poppins', sans-serif; background: var(--bg); margin: 0; display: flex; color: #1e293b; }
        .sidebar { width: 260px; background: var(--sidebar); height: 100vh; padding: 30px 20px; box-shadow: 4px 0 24px rgba(0,0,0,0.03); position: fixed; z-index: 100;}
        .logo { font-size: 22px; font-weight: 600; color: #0f172a; margin-bottom: 40px; display: flex; align-items: center; gap: 10px; text-decoration:none; }
        .menu-item { display: flex; align-items: center; padding: 14px 18px; color: #64748b; text-decoration: none; border-radius: 12px; margin-bottom: 8px; transition: 0.2s; }
        .menu-item:hover { background: #f8fafc; color: #0f172a; }
        .menu-item.active { background: #f0f9ff; color: var(--blue); font-weight: 600; }
        .main { margin-left: 260px; padding: 40px; width: calc(100% - 260px); position: relative; }
        .stat-card { background: white; padding: 24px; border-radius: 24px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.04); border-left: 6px solid; }
        .recipe-highlight { background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border: 1px solid #bbf7d0; border-radius: 24px; padding: 25px; margin-bottom: 30px; }
        .premium-btn { position: absolute; top: 40px; right: 40px; background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; padding: 12px 24px; border-radius: 14px; font-weight: 600; text-decoration: none; border: none; }
        .uzman-alert { padding: 15px; border-radius: 15px; display: flex; justify-content: space-between; align-items: center; border: 1px solid; margin-bottom: 20px; }
    </style>
</head>
<body>

<div class="sidebar">
    <div style="margin-bottom: 30px;">
        <a href="panel.php" class="logo" style="margin-bottom: 5px;">🩺 Sağlık Takip</a>
        <?php if($is_premium == 1): ?>
            <div style="margin-left: 32px;">
                <span style="background: linear-gradient(135deg, #f59e0b, #fbbf24); color: white; padding: 2px 10px; border-radius: 20px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; box-shadow: 0 2px 4px rgba(245, 158, 11, 0.3);">
                    <i class="fas fa-crown" style="font-size: 9px;"></i> PREMIUM
                </span>
            </div>
        <?php endif; ?>
    </div>
    
    <?php 
        $current_page = basename($_SERVER['PHP_SELF']); 
        if(!function_exists('isActive')){
            function isActive($page, $current) { return ($page == $current) ? 'active' : ''; }
        }
    ?>

    <a href="panel.php" class="menu-item <?php echo isActive('panel.php', $current_page); ?>">🏠 Özet Paneli</a>
    <a href="beslenme.php" class="menu-item <?php echo isActive('beslenme.php', $current_page); ?>">🥗 Beslenme</a>
    <a href="egzersiz.php" class="menu-item <?php echo isActive('egzersiz.php', $current_page); ?>">🏋️ Egzersiz</a>

    <?php if($is_premium == 1): ?>
        <a href="sorularim.php" class="menu-item <?php echo isActive('sorularim.php', $current_page); ?>">
            <i class="fas fa-envelope-open-text me-2"></i> Uzmana Sorular
        </a>
    <?php endif; ?>

    <a href="gelisim.php" class="menu-item <?php echo isActive('gelisim.php', $current_page); ?>">📈 Gelişim</a>
    <a href="rozetlerim.php" class="menu-item <?php echo isActive('rozetlerim.php', $current_page); ?>">🏆 Rozetlerim</a>
    <a href="turnuva.php" class="menu-item <?php echo isActive('turnuva.php', $current_page); ?>">
        <i class="fas fa-trophy me-2"></i> Turnuva
    </a>
    <a href="profil.php" class="menu-item <?php echo isActive('profil.php', $current_page); ?>">👤 Profil</a>
    
    <a href="cikis.php" class="menu-item" style="color:#ef4444; margin-top: 40px;">🚪 Çıkış Yap</a>
</div>

<div class="main">
    <?php if(!$is_premium): ?>
        <button type="button" class="premium-btn" data-bs-toggle="modal" data-bs-target="#premiumInfoModal">
            <i class="fas fa-crown"></i> Premium Edinin
        </button>
    <?php endif; ?>

    <h1>Hoş Geldin, <?php echo htmlspecialchars($user_data['ad_soyad']); ?>! 👋</h1>

    <?php if($is_premium == 1): ?>
        <?php 
        $bildirim_sorgu = $conn->prepare("SELECT id FROM uzman_sorulari WHERE danisan_id = ? AND durum = 'cevaplandi' LIMIT 1");
        $bildirim_sorgu->execute([$user_id]);
        if ($bildirim_sorgu->fetch()): ?>
            <div class="alert shadow-sm d-flex align-items-center justify-content-between" 
                 style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); border: none; border-radius: 16px; color: white; padding: 20px; margin-top: 20px;">
                <div class="d-flex align-items-center">
                    <div style="background: rgba(255,255,255,0.2); padding: 10px; border-radius: 12px; margin-right: 15px;">
                        <i class="fas fa-comment-medical fa-lg"></i>
                    </div>
                    <div>
                        <h5 class="mb-0" style="font-weight: 600;">Uzmanınız Sorunuzu Yanıtladı!</h5>
                        <p class="mb-0 small" style="opacity: 0.9;">Yeni bir mesajınız var. Detaylar için sayfaya gidin.</p>
                    </div>
                </div>
                <a href="sorularim.php" class="btn btn-light btn-sm fw-bold" style="border-radius: 10px; color: #059669; padding: 8px 15px;">
                    Cevabı Gör <i class="fas fa-arrow-right ms-1"></i>
                </a>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px;">
        <?php if (!$has_diyetisyen): ?>
            <div class="uzman-alert" style="background: #ecfdf5; border-color: #a7f3d0;">
                <span style="color: #065f46; font-weight:600;">🥗 Diyetisyeniniz seçilmedi.</span>
                <a href="uzman_secmesi.php?rol=diyetisyen" class="btn btn-success btn-sm">Seç</a>
            </div>
        <?php endif; ?>
        <?php if (!$has_hoca): ?>
            <div class="uzman-alert" style="background: #eff6ff; border-color: #bfdbfe;">
                <span style="color: #1e40af; font-weight:600;">🏋️ Spor hocanız seçilmedi.</span>
                <a href="uzman_secmesi.php?rol=hoca" class="btn btn-primary btn-sm">Seç</a>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($gunun_tarifi): ?>
        <div class="recipe-highlight">
            <h3 style="color: #166534; margin: 0;"><i class="fas fa-utensils"></i> Günün Sağlıklı Tarifi</h3>
            <h4 style="margin: 10px 0;"><?php echo htmlspecialchars($gunun_tarifi['tarif_baslik']); ?></h4>
            <p style="font-size: 14px; color: #374151;"><?php echo nl2br(htmlspecialchars($gunun_tarifi['tarif_icerik'])); ?></p>
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <small style="color: #15803d; font-weight: 600;">👨‍⚕️ Diyetisyen: <?php echo htmlspecialchars($gunun_tarifi['ad_soyad']); ?></small>
                <form action="islem_v2.php?is=puan_ver" method="POST" style="display: flex; gap: 5px;">
                    <input type="hidden" name="tarif_id" value="<?php echo $gunun_tarifi['id']; ?>">
                    <?php for($i=1; $i<=5; $i++): ?>
                        <button type="submit" name="puan" value="<?php echo $i; ?>" class="btn btn-sm <?php echo ($mevcut_puan == $i) ? 'btn-success' : 'btn-outline-success'; ?>"><?php echo $i; ?>⭐</button>
                    <?php endfor; ?>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; margin-bottom: 40px; margin-top: 20px;">
        <div class="stat-card" style="border-color: var(--blue);">
            <div style="font-size: 14px; color: #64748b;">💧 Su</div>
            <div style="font-size: 24px; font-weight: 600;"><?php echo $su; ?> / 2.5 L</div>
        </div>
        <div class="stat-card" style="border-color: var(--orange);">
            <div style="font-size: 14px; color: #64748b;">🔥 Kalori</div>
            <div style="font-size: 24px; font-weight: 600;"><?php echo round($alinan); ?> / 2000</div>
        </div>
        <div class="stat-card" style="border-color: var(--green);">
            <div style="font-size: 14px; color: #64748b;">😴 Uyku</div>
            <div style="font-size: 24px; font-weight: 600;"><?php echo $uyku; ?> / 8 Saat</div>
        </div>
    </div>

    <div style="background: white; padding: 30px; border-radius: 24px; box-shadow: 0 10px 15px rgba(0,0,0,0.04);">
        <h3>➕ Bugünün Verilerini Gir</h3>
        <?php if ($mevcut_kayit): ?>
            <div class="alert alert-warning text-center">⚠️ Bugün zaten kayıt yaptınız.</div>
        <?php else: ?>
            <form action="islem_v2.php?is=verileri_kaydet" method="POST">
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 20px;">
                    <input type="number" step="0.1" name="su_miktari" placeholder="Su (Litre)" required class="form-control">
                    <input type="number" step="0.1" name="uyku_suresi" placeholder="Uyku (Saat)" required class="form-control">
                    <input type="number" name="alinan_kalori" placeholder="Alınan Kalori" required class="form-control">
                    <input type="number" name="yakilan_kalori" placeholder="Yakılan Kalori" required class="form-control">
                    <input type="number" name="spor_suresi" placeholder="Spor (Dakika)" required class="form-control">
                    <input type="number" step="0.1" name="guncel_kilo" placeholder="Kilo (kg)" required class="form-control">
                </div>
                <button type="submit" class="btn btn-primary w-100 p-3" style="border-radius:14px; font-weight:600;">Kaydı Sisteme İşle</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<div class="modal fade" id="premiumInfoModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="border-radius: 30px;">
      <div class="modal-header bg-warning text-white">
        <h5 class="modal-title">Neden Premium?</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4 text-center">
        <p>Uzmanlarınızla birebir iletişim kurun ve size özel gelişim grafiklerine erişin.</p>
        <button class="btn btn-warning w-100" onclick="alert('Yakında!')">Planları İncele</button>
      </div>
    </div>
  </div>
</div>

</body>
</html>