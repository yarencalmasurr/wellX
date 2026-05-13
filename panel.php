<?php
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

// --- UZMAN EŞLEŞME KONTROLLERİ (İsimleriyle Birlikte Çekilir) ---
$diy_kontrol = $conn->prepare("
    SELECT k.ad_soyad 
    FROM uzman_danisan_eslesmeleri ude 
    JOIN kullanicilar k ON ude.uzman_id = k.id 
    WHERE ude.danisan_id = ? AND ude.uzman_rol = 'diyetisyen'
");
$diy_kontrol->execute([$user_id]);
$matched_diyetisyen = $diy_kontrol->fetch(PDO::FETCH_ASSOC);

$hoca_kontrol = $conn->prepare("
    SELECT k.ad_soyad 
    FROM uzman_danisan_eslesmeleri ude 
    JOIN kullanicilar k ON ude.uzman_id = k.id 
    WHERE ude.danisan_id = ? AND ude.uzman_rol = 'hoca'
");
$hoca_kontrol->execute([$user_id]);
$matched_hoca = $hoca_kontrol->fetch(PDO::FETCH_ASSOC);

// --- BİLDİRİM KONTROLLERİ (Sadece Okunmamış Planlar) ---
$bildirim_beslenme = $conn->prepare("SELECT id FROM beslenme_planlari WHERE user_id = ? AND DATE(kayit_tarihi) = ? AND okundu = 0 LIMIT 1");
$bildirim_beslenme->execute([$user_id, $bugun]);
$yeni_beslenme = $bildirim_beslenme->fetch();

$bildirim_egzersiz = $conn->prepare("SELECT id FROM egzersiz_planlari WHERE user_id = ? AND DATE(kayit_tarihi) = ? AND okundu = 0 LIMIT 1");
$bildirim_egzersiz->execute([$user_id, $bugun]);
$yeni_egzersiz = $bildirim_egzersiz->fetch();

// --- GENEL GÜNÜN ANTRENMANI (Duyuru) ---
$genel_antrenman_sorgu = $conn->query("SELECT * FROM gunun_antrenmani ORDER BY id DESC LIMIT 1");
$genel_antrenman = $genel_antrenman_sorgu->fetch(PDO::FETCH_ASSOC);

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
$stat_sorgu = $conn->prepare("
    SELECT 
        SUM(su_miktari) as t_su, 
        SUM(alinan_kalori) as t_alinan, 
        SUM(uyku_suresi) as t_uyku,
        SUM(spor_suresi) as t_spor,
        (SELECT guncel_kilo FROM aktivite_kayitlari WHERE user_id = ? ORDER BY id DESC LIMIT 1) as son_kilo
    FROM aktivite_kayitlari 
    WHERE user_id = ? AND kayit_tarihi = ?
");
$stat_sorgu->execute([$user_id, $user_id, $bugun]);
$veri = $stat_sorgu->fetch(PDO::FETCH_ASSOC);

$su = $veri['t_su'] ?? 0;
$alinan = $veri['t_alinan'] ?? 0;
$uyku = $veri['t_uyku'] ?? 0;
$spor = $veri['t_spor'] ?? 0;
$son_kilo = $veri['son_kilo'] ?? "0";

$kontrol = $conn->prepare("SELECT id FROM aktivite_kayitlari WHERE user_id = ? AND kayit_tarihi = ?");
$kontrol->execute([$user_id, $bugun]);
$mevcut_kayit = $kontrol->fetch();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Sağlık Takip | Panel</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <style>
        :root { 
            --blue: #3b82f6; --orange: #f59e0b; --green: #10b981; --purple: #8b5cf6; --pink: #ec4899; 
            --bg: #f8fafc; --sidebar: #ffffff; --text-main: #1e293b; --text-muted: #64748b;
        }
        body { font-family: 'Poppins', sans-serif; background: var(--bg); margin: 0; display: flex; color: var(--text-main); }
        
        /* Modern Sidebar */
        .sidebar { width: 260px; background: var(--sidebar); height: 100vh; padding: 30px 20px; box-shadow: 4px 0 24px rgba(0,0,0,0.03); position: fixed; z-index: 100;}
        .logo { font-size: 22px; font-weight: 700; color: #0f172a; margin-bottom: 40px; display: flex; align-items: center; gap: 10px; text-decoration:none; }
        .logo i { color: var(--blue); }
        .menu-item { display: flex; align-items: center; padding: 14px 18px; color: var(--text-muted); text-decoration: none; border-radius: 14px; margin-bottom: 8px; transition: 0.3s; font-weight: 500;}
        .menu-item:hover { background: #f1f5f9; color: var(--text-main); }
        .menu-item.active { background: #eff6ff; color: var(--blue); font-weight: 600; }
        
        /* Main Content */
        .main { margin-left: 260px; padding: 40px 50px; width: calc(100% - 260px); position: relative; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .page-header h1 { font-size: 28px; font-weight: 700; margin: 0; }

        .premium-btn { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; padding: 12px 24px; border-radius: 14px; font-weight: 600; text-decoration: none; border: none; box-shadow: 0 4px 15px rgba(245, 158, 11, 0.3); transition: 0.3s;}
        .premium-btn:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(245, 158, 11, 0.4); color: white;}

        /* EN ÜSTTE UZMAN KARTLARI */
        .experts-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px; }
        .expert-card { padding: 20px; border-radius: 20px; display: flex; justify-content: space-between; align-items: center; border: 1px solid transparent; background: white; box-shadow: 0 4px 15px rgba(0,0,0,0.02);}
        .expert-card.diet { background: #f0fdf4; border-color: #bbf7d0; }
        .expert-card.trainer { background: #eff6ff; border-color: #bfdbfe; }
        .expert-card.unassigned { background: #fff1f2; border-color: #fecdd3; }
        
        .expert-info { display: flex; align-items: center; gap: 15px; }
        .expert-icon { width: 45px; height: 45px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; }
        .diet .expert-icon { background: #dcfce7; color: #166534; }
        .trainer .expert-icon { background: #dbeafe; color: #1e40af; }
        .unassigned .expert-icon { background: #ffe4e6; color: #9f1239; }
        
        .expert-details h4 { margin: 0 0 4px 0; font-size: 14px; color: var(--text-muted); font-weight: 500;}
        .expert-details strong { font-size: 16px; color: var(--text-main); }
        .unassigned .expert-details strong { color: #9f1239; }

        /* BİLDİRİMLER (ALT ALTA) */
        .notifications-stack { display: flex; flex-direction: column; gap: 15px; margin-bottom: 30px; }
        .alert-item { border-radius: 16px; padding: 18px 24px; color: white; display: flex; justify-content: space-between; align-items: center; border: none; box-shadow: 0 4px 15px rgba(0,0,0,0.05);}
        .alert-diet { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
        .alert-trainer { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); }
        .btn-alert { background: rgba(255,255,255,0.2); color: white; border: none; padding: 8px 16px; border-radius: 10px; font-weight: 600; text-decoration: none; transition: 0.2s; }
        .btn-alert:hover { background: rgba(255,255,255,0.3); color: white; }

        /* GÜNÜN PLANI (Yan Yana) */
        .daily-plan-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px; }
        .plan-card { background: white; padding: 25px; border-radius: 24px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); border: 1px solid #f1f5f9; display: flex; flex-direction: column;}
        .plan-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 15px; }
        .plan-header h3 { margin: 0; font-size: 18px; font-weight: 600; display: flex; align-items: center; gap: 10px; }
        .plan-content { color: var(--text-muted); font-size: 14px; line-height: 1.6; flex-grow: 1; }
        .plan-footer { margin-top: 20px; padding-top: 15px; border-top: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center;}

        /* Star Rating */
        .rating-form { display: flex; gap: 5px; }
        .rating-btn { background: #f8fafc; border: 1px solid #e2e8f0; color: var(--text-muted); padding: 4px 10px; border-radius: 8px; font-size: 12px; cursor: pointer; transition: 0.2s;}
        .rating-btn.active, .rating-btn:hover { background: #fef3c7; border-color: #f59e0b; color: #d97706; }

        /* İSTATİSTİKLER */
        .stats-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 20px; margin-bottom: 40px; }
        .stat-card { background: white; padding: 24px; border-radius: 24px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); text-align: center; border: 1px solid #f1f5f9; position: relative; overflow: hidden;}
        .stat-card::before { content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 4px; }
        .stat-su::before { background: var(--blue); }
        .stat-kalori::before { background: var(--orange); }
        .stat-uyku::before { background: var(--green); }
        .stat-spor::before { background: var(--purple); }
        .stat-kilo::before { background: var(--pink); }
        .stat-title { font-size: 14px; color: var(--text-muted); margin-bottom: 8px; font-weight: 500;}
        .stat-value { font-size: 22px; font-weight: 700; color: var(--text-main); }

        /* VERİ GİRİŞ FORMU */
        .entry-form-card { background: white; padding: 35px; border-radius: 24px; box-shadow: 0 10px 25px rgba(0,0,0,0.04); border: 1px solid #f1f5f9;}
        .form-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 25px; }
        .form-control { border-radius: 14px; padding: 14px 18px; border: 1px solid #e2e8f0; background: #f8fafc; font-size: 14px; transition: 0.3s;}
        .form-control:focus { background: white; border-color: var(--blue); box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1); }
        .btn-submit { background: var(--blue); color: white; padding: 16px; border-radius: 14px; font-weight: 600; width: 100%; border: none; transition: 0.3s;}
        .btn-submit:hover { background: #2563eb; transform: translateY(-2px); box-shadow: 0 8px 20px rgba(59, 130, 246, 0.2); }
    </style>
</head>
<body>

<div class="sidebar">
    <a href="panel.php" class="logo"><i class="fas fa-heartbeat"></i> Sağlık Takip</a>
    <?php if($is_premium == 1): ?>
        <div style="margin: -25px 0 30px 40px;">
            <span style="background: linear-gradient(135deg, #f59e0b, #fbbf24); color: white; padding: 4px 12px; border-radius: 20px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">
                <i class="fas fa-crown"></i> PREMIUM
            </span>
        </div>
    <?php endif; ?>
    
    <?php 
        $current_page = basename($_SERVER['PHP_SELF']); 
        if(!function_exists('isActive')){
            function isActive($page, $current) { return ($page == $current) ? 'active' : ''; }
        }
    ?>

    <a href="panel.php" class="menu-item <?php echo isActive('panel.php', $current_page); ?>"><i class="fas fa-home" style="width:25px;"></i> Özet Paneli</a>
    <a href="beslenme.php" class="menu-item <?php echo isActive('beslenme.php', $current_page); ?>"><i class="fas fa-apple-alt" style="width:25px;"></i> Beslenme</a>
    <a href="egzersiz.php" class="menu-item <?php echo isActive('egzersiz.php', $current_page); ?>"><i class="fas fa-dumbbell" style="width:25px;"></i> Egzersiz</a>

    <a href="sorularim.php" class="menu-item <?php echo isActive('sorularim.php', $current_page); ?>">
        <i class="fas fa-envelope-open-text" style="width:25px;"></i> Uzmana Sorular
        <?php if($is_premium == 0): ?><i class="fas fa-lock ms-auto" style="font-size: 12px;"></i><?php endif; ?>
    </a>

    <a href="gelisim.php" class="menu-item <?php echo isActive('gelisim.php', $current_page); ?>"><i class="fas fa-chart-line" style="width:25px;"></i> Gelişim</a>
    <a href="rozetlerim.php" class="menu-item <?php echo isActive('rozetlerim.php', $current_page); ?>"><i class="fas fa-medal" style="width:25px;"></i> Rozetlerim</a>
    <a href="turnuva.php" class="menu-item <?php echo isActive('turnuva.php', $current_page); ?>"><i class="fas fa-trophy" style="width:25px;"></i> Turnuva</a>
    <a href="profil.php" class="menu-item <?php echo isActive('profil.php', $current_page); ?>"><i class="fas fa-user" style="width:25px;"></i> Profil</a>
    
    <a href="cikis.php" class="menu-item" style="color:#ef4444; margin-top: 40px;"><i class="fas fa-sign-out-alt" style="width:25px;"></i> Çıkış Yap</a>
</div>

<div class="main">
    <div class="page-header">
        <h1>Hoş Geldin, <?php echo htmlspecialchars($user_data['ad_soyad']); ?>! 👋</h1>
        <?php if(!$is_premium): ?>
            <button type="button" class="premium-btn" data-bs-toggle="modal" data-bs-target="#premiumInfoModal">
                <i class="fas fa-crown"></i> Premium Edinin
            </button>
        <?php endif; ?>
    </div>

    <div class="experts-grid">
        <?php if ($matched_diyetisyen): ?>
            <div class="expert-card diet">
                <div class="expert-info">
                    <div class="expert-icon"><i class="fas fa-user-md"></i></div>
                    <div class="expert-details">
                        <h4>Diyetisyeniniz</h4>
                        <strong><?php echo htmlspecialchars($matched_diyetisyen['ad_soyad']); ?></strong>
                    </div>
                </div>
                <a href="uzman_secmesi.php?rol=diyetisyen" class="btn btn-outline-success btn-sm" style="border-radius:10px;">Değiştir</a>
            </div>
        <?php else: ?>
            <div class="expert-card unassigned">
                <div class="expert-info">
                    <div class="expert-icon"><i class="fas fa-exclamation-circle"></i></div>
                    <div class="expert-details">
                        <h4>Diyetisyen</h4>
                        <strong>Henüz Seçilmedi</strong>
                    </div>
                </div>
                <a href="uzman_secmesi.php?rol=diyetisyen" class="btn btn-danger btn-sm" style="border-radius:10px;">Seç</a>
            </div>
        <?php endif; ?>

        <?php if ($matched_hoca): ?>
            <div class="expert-card trainer">
                <div class="expert-info">
                    <div class="expert-icon"><i class="fas fa-running"></i></div>
                    <div class="expert-details">
                        <h4>Spor Hocanız</h4>
                        <strong><?php echo htmlspecialchars($matched_hoca['ad_soyad']); ?></strong>
                    </div>
                </div>
                <a href="uzman_secmesi.php?rol=hoca" class="btn btn-outline-primary btn-sm" style="border-radius:10px;">Değiştir</a>
            </div>
        <?php else: ?>
            <div class="expert-card unassigned">
                <div class="expert-info">
                    <div class="expert-icon"><i class="fas fa-exclamation-circle"></i></div>
                    <div class="expert-details">
                        <h4>Spor Hocası</h4>
                        <strong>Henüz Seçilmedi</strong>
                    </div>
                </div>
                <a href="uzman_secmesi.php?rol=hoca" class="btn btn-danger btn-sm" style="border-radius:10px;">Seç</a>
            </div>
        <?php endif; ?>
    </div>

    <div class="notifications-stack">
        <?php if ($yeni_beslenme): ?>
            <div class="alert-item alert-diet">
                <div><i class="fas fa-apple-alt me-2"></i> <strong>Diyetisyeniniz yeni bir beslenme planı paylaştı!</strong></div>
                <a href="beslenme.php" class="btn-alert">İncele</a>
            </div>
        <?php endif; ?>

        <?php if ($yeni_egzersiz): ?>
            <div class="alert-item alert-trainer">
                <div><i class="fas fa-dumbbell me-2"></i> <strong>Hocanız bugün için yeni bir egzersiz programı hazırladı!</strong></div>
                <a href="egzersiz.php" class="btn-alert">Programa Git</a>
            </div>
        <?php endif; ?>
    </div>

    <div class="daily-plan-grid">
        <div class="plan-card" style="border-left: 4px solid var(--blue);">
            <div class="plan-header">
                <h3 style="color: var(--blue);"><i class="fas fa-bullhorn"></i> Günün Antrenmanı</h3>
            </div>
            <?php if ($genel_antrenman): ?>
                <h5 style="color: var(--text-main); font-weight: 600;"><?php echo htmlspecialchars($genel_antrenman['antrenman_baslik']); ?></h5>
                <div class="plan-content"><?php echo nl2br(htmlspecialchars($genel_antrenman['antrenman_icerik'])); ?></div>
            <?php else: ?>
                <div class="plan-content" style="display:flex; align-items:center; justify-content:center; text-align:center; height:100%;">
                    Henüz günün antrenmanı paylaşılmadı.
                </div>
            <?php endif; ?>
        </div>

        <div class="plan-card" style="border-left: 4px solid var(--green);">
            <div class="plan-header">
                <h3 style="color: var(--green);"><i class="fas fa-utensils"></i> Günün Sağlıklı Tarifi</h3>
            </div>
            <?php if ($gunun_tarifi): ?>
                <h5 style="color: var(--text-main); font-weight: 600;"><?php echo htmlspecialchars($gunun_tarifi['tarif_baslik']); ?></h5>
                <div class="plan-content"><?php echo nl2br(htmlspecialchars($gunun_tarifi['tarif_icerik'])); ?></div>
                <div class="plan-footer">
                    <small style="color: var(--text-muted);"><i class="fas fa-user-md me-1"></i> <?php echo htmlspecialchars($gunun_tarifi['ad_soyad']); ?></small>
                    <form action="islem_v2.php?is=puan_ver" method="POST" class="rating-form">
                        <input type="hidden" name="tarif_id" value="<?php echo $gunun_tarifi['id']; ?>">
                        <?php for($i=1; $i<=5; $i++): ?>
                            <button type="submit" name="puan" value="<?php echo $i; ?>" class="rating-btn <?php echo ($mevcut_puan == $i) ? 'active' : ''; ?>"><?php echo $i; ?><i class="fas fa-star ms-1"></i></button>
                        <?php endfor; ?>
                    </form>
                </div>
            <?php else: ?>
                <div class="plan-content" style="display:flex; align-items:center; justify-content:center; text-align:center; height:100%;">
                    Bugün için henüz bir tarif paylaşılmadı.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card stat-su">
            <div class="stat-title">💧 İçilen Su</div>
            <div class="stat-value"><?php echo $su; ?> <span style="font-size:14px; color:var(--text-muted); font-weight:500;">/ 2.5 L</span></div>
        </div>
        <div class="stat-card stat-kalori">
            <div class="stat-title">🔥 Alınan Kalori</div>
            <div class="stat-value"><?php echo round($alinan); ?> <span style="font-size:14px; color:var(--text-muted); font-weight:500;">/ 2000</span></div>
        </div>
        <div class="stat-card stat-uyku">
            <div class="stat-title">😴 Uyku Süresi</div>
            <div class="stat-value"><?php echo $uyku; ?> <span style="font-size:14px; color:var(--text-muted); font-weight:500;">Saat</span></div>
        </div>
        <div class="stat-card stat-spor">
            <div class="stat-title">🏃 Spor Süresi</div>
            <div class="stat-value"><?php echo $spor; ?> <span style="font-size:14px; color:var(--text-muted); font-weight:500;">Dk</span></div>
        </div>
        <div class="stat-card stat-kilo">
            <div class="stat-title">⚖️ Güncel Kilo</div>
            <div class="stat-value"><?php echo $son_kilo; ?> <span style="font-size:14px; color:var(--text-muted); font-weight:500;">kg</span></div>
        </div>
    </div>

    <div class="entry-form-card">
        <h3 style="margin-top:0; margin-bottom: 25px; font-weight:600; display:flex; align-items:center; gap:10px;">
            <i class="fas fa-plus-circle text-primary"></i> Bugünün Verilerini Gir
        </h3>
        
        <?php if ($mevcut_kayit): ?>
            <div class="alert alert-warning" style="border-radius:12px; border:none; background:#fef3c7; color:#d97706; display:flex; align-items:center; gap:10px;">
                <i class="fas fa-exclamation-triangle"></i> Bugün kayıt yaptınız. Yeni veriler eskilerin üzerine eklenir.
            </div>
        <?php endif; ?>

        <form action="islem_v2.php?is=verileri_kaydet" method="POST">
            <div class="form-grid">
                <input type="number" step="0.1" name="su_miktari" placeholder="Su (Litre)" required class="form-control">
                <input type="number" step="0.1" name="uyku_suresi" placeholder="Uyku (Saat)" required class="form-control">
                <input type="number" name="alinan_kalori" placeholder="Alınan Kalori" required class="form-control">
                <input type="number" name="yakilan_kalori" placeholder="Yakılan Kalori" required class="form-control">
                <input type="number" name="spor_suresi" placeholder="Spor (Dakika)" required class="form-control">
                <input type="number" step="0.1" name="guncel_kilo" placeholder="Kilo (kg)" value="<?php echo $son_kilo; ?>" required class="form-control">
            </div>
            <button type="submit" class="btn-submit"><i class="fas fa-save me-2"></i> Verileri Sisteme İşle</button>
        </form>
    </div>
</div>

<div class="modal fade" id="premiumInfoModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="border-radius: 24px; border:none;">
      <div class="modal-header" style="background: linear-gradient(135deg, #f59e0b, #d97706); color: white; border-radius: 24px 24px 0 0;">
        <h5 class="modal-title fw-bold"><i class="fas fa-crown me-2"></i> Neden Premium?</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4 text-center">
        <div style="font-size: 48px; color: #f59e0b; margin-bottom: 15px;"><i class="fas fa-star"></i></div>
        <p style="color: var(--text-main); font-size: 16px;">Uzmanlarınızla birebir iletişim kurun, fotoğraf yükleyin ve size özel gelişim grafiklerine erişin.</p>
        <a href="premium_planlar.php" class="btn" style="background: var(--text-main); color:white; padding: 12px 24px; border-radius: 12px; text-decoration:none; display:inline-block; margin-top: 10px; font-weight:600;">Planları İncele</a>
      </div>
    </div>
  </div>
</div>

</body>
</html>