<?php
session_start(); 
include 'baglan.php'; 

// oturum kontrolü
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// kullanıcı verilerini çek
$user_sorgu = $conn->prepare("SELECT * FROM kullanicilar WHERE id = ?");
$user_sorgu->execute([$user_id]);
$user_data = $user_sorgu->fetch(PDO::FETCH_ASSOC);

//  danışan değilse erişimi engelle
if (!$user_data || ($_SESSION['rol'] != 'danışan' && $_SESSION['rol'] != 'danisan')) {
    header("Location: index.php"); 
    exit();
}

$bugun = date('Y-m-d');
$is_premium = $user_data['is_premium'] ?? 0;

// uzman eşleşme kontrolü
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

//bildirim kontrolleri
$bildirim_beslenme = $conn->prepare("SELECT id FROM beslenme_planlari WHERE user_id = ? AND DATE(kayit_tarihi) = ? AND okundu = 0 LIMIT 1");
$bildirim_beslenme->execute([$user_id, $bugun]);
$yeni_beslenme = $bildirim_beslenme->fetch();

$bildirim_egzersiz = $conn->prepare("SELECT id FROM egzersiz_planlari WHERE user_id = ? AND DATE(kayit_tarihi) = ? AND okundu = 0 LIMIT 1");
$bildirim_egzersiz->execute([$user_id, $bugun]);
$yeni_egzersiz = $bildirim_egzersiz->fetch();

// günün antrenmanı
$genel_antrenman_sorgu = $conn->query("SELECT * FROM gunun_antrenmani WHERE DATE(ekleme_tarihi) = CURDATE() ORDER BY id DESC LIMIT 1");
$genel_antrenman = $genel_antrenman_sorgu->fetch(PDO::FETCH_ASSOC);
// günün tarifi
$tarif_sorgu = $conn->prepare("
    SELECT t.*, k.ad_soyad 
    FROM gunun_tarifi t 
    JOIN kullanicilar k ON t.diyetisyen_id = k.id 
    WHERE t.ekleme_tarihi >= DATE_SUB(NOW(), INTERVAL 1 DAY)
    ORDER BY t.id DESC LIMIT 1
");
$tarif_sorgu->execute();
$gunun_tarifi = $tarif_sorgu->fetch(PDO::FETCH_ASSOC);

// tarife verilen puan
$mevcut_puan = 0;
if ($gunun_tarifi) {
    $puan_cek = $conn->prepare("SELECT puan FROM tarif_puanlari WHERE tarif_id = ? AND user_id = ?");
    $puan_cek->execute([$gunun_tarifi['id'], $user_id]);
    $puan_veri = $puan_cek->fetch();
    $mevcut_puan = $puan_veri['puan'] ?? 0;
}

// istatistikler
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
    <title>WellX | Danışan Paneli</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <style>
        :root { 
            --blue: #3b82f6; --orange: #f59e0b; --green: #10b981; --purple: #8b5cf6; --pink: #ec4899; 
            --text-main: #1e293b; --text-muted: #64748b;
            --glass-bg: rgba(255, 255, 255, 0.75);
            --glass-border: rgba(255, 255, 255, 0.6);
        }
        
        body { 
            font-family: 'Poppins', sans-serif; 
            margin: 0; display: flex; color: var(--text-main); 
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 50%, #bae6fd 100%);
            background-attachment: fixed;
            min-height: 100vh;
        }

        #particles-js { position: fixed; width: 100%; height: 100%; top: 0; left: 0; z-index: 0; pointer-events: none; }
        
        /* sidebar tasarımı */
        .sidebar { 
            width: 260px; height: 100vh; padding: 30px 20px; position: fixed; z-index: 100;
            background: rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-right: 1px solid var(--glass-border);
            box-shadow: 10px 0 30px rgba(0,0,0,0.03);
            display: flex; flex-direction: column;
        }
        
        .sidebar h2 { font-size: 28px; font-weight: 800; color: #111827; margin-bottom: 10px; letter-spacing: -1px; display: flex; align-items: center; gap: 10px;}
        .sidebar h2 i { color: #ef4444; filter: drop-shadow(0 0 8px rgba(239,68,68,0.4)); }

        .menu-item { 
            display: flex; align-items: center; padding: 14px 18px; color: var(--text-muted); 
            text-decoration: none; border-radius: 16px; margin-bottom: 8px; transition: all 0.3s ease; font-weight: 500;
            border: 1px solid transparent;
        }
        
        .menu-item i { transition: 0.3s; width: 25px; }
        .menu-item:hover { 
            background: rgba(255, 255, 255, 0.9); color: var(--blue); transform: translateX(5px);
            border-color: rgba(255,255,255,0.8); box-shadow: 0 4px 15px rgba(59,130,246,0.05);
        }
        .menu-item:hover i { transform: scale(1.1); }
        .menu-item.active { 
            background: linear-gradient(135deg, #dbeafe, #eff6ff); color: var(--blue); font-weight: 700; 
            box-shadow: 0 8px 20px rgba(59,130,246,0.1); border-color: white;
        }

        .menu-item:nth-of-type(1) i { color: #3b82f6; }
        .menu-item:nth-of-type(2) i { color: #10b981; }
        .menu-item:nth-of-type(3) i { color: #8b5cf6; }
        .menu-item:nth-of-type(4) i { color: #f59e0b; }
        .menu-item:nth-of-type(5) i { color: #06b6d4; }
        .menu-item:nth-of-type(6) i { color: #ec4899; }
        .menu-item:nth-of-type(7) i { color: #f97316; }
        .menu-item:nth-of-type(8) i { color: #6366f1; }

        .sidebar .logout-btn { margin-top: auto !important; background: rgba(254, 226, 226, 0.6); color: #ef4444 !important; font-weight: 600; }
        .sidebar .logout-btn:hover { background: #fee2e2; color: #dc2626 !important; transform: translateX(0) translateY(-2px); }

        /* ana içerik */
        .main { margin-left: 260px; padding: 40px 50px; width: calc(100% - 260px); position: relative; z-index: 10; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .page-header h1 { font-size: 32px; font-weight: 800; margin: 0; letter-spacing: -1px; color: #0f172a;}

        .premium-btn { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; padding: 12px 24px; border-radius: 14px; font-weight: 600; text-decoration: none; border: none; box-shadow: 0 4px 15px rgba(245, 158, 11, 0.3); transition: 0.3s;}
        .premium-btn:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(245, 158, 11, 0.4); color: white;}

        /* cam kart görünümü */
        .glass-card {
            background: var(--glass-bg); backdrop-filter: blur(15px); -webkit-backdrop-filter: blur(15px);
            border-radius: 24px; border: 1px solid var(--glass-border);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.03); transition: 0.3s ease;
        }
        .glass-card:hover { transform: translateY(-4px); box-shadow: 0 20px 40px rgba(0, 0, 0, 0.06); }

        /* uzman kartları */
        .experts-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px; }
        .expert-card { padding: 20px; display: flex; justify-content: space-between; align-items: center; }
        
        .expert-info { display: flex; align-items: center; gap: 15px; }
        .expert-icon { width: 50px; height: 50px; border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 20px; }
        .diet .expert-icon { background: rgba(220, 252, 231, 0.8); color: #166534; }
        .trainer .expert-icon { background: rgba(219, 234, 254, 0.8); color: #1e40af; }
        .unassigned .expert-icon { background: rgba(255, 228, 230, 0.8); color: #9f1239; }
        
        .expert-details h4 { margin: 0 0 4px 0; font-size: 14px; color: var(--text-muted); font-weight: 500;}
        .expert-details strong { font-size: 16px; color: var(--text-main); }

        /* bildirimler */
        .notifications-stack { display: flex; flex-direction: column; gap: 15px; margin-bottom: 30px; }
        .alert-item { padding: 18px 24px; color: white; display: flex; justify-content: space-between; align-items: center; border: none;}
        .alert-diet { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
        .alert-trainer { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); }
        .btn-alert { background: rgba(255,255,255,0.2); color: white; border: none; padding: 8px 16px; border-radius: 12px; font-weight: 600; text-decoration: none; transition: 0.2s; }
        .btn-alert:hover { background: rgba(255,255,255,0.3); color: white; }

        /* günün planı */
        .daily-plan-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px; }
        .plan-card { padding: 25px; display: flex; flex-direction: column;}
        .plan-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 15px; }
        .plan-header h3 { margin: 0; font-size: 18px; font-weight: 700; display: flex; align-items: center; gap: 10px; }
        .plan-content { color: var(--text-main); font-size: 14px; line-height: 1.6; flex-grow: 1; }
        .plan-footer { margin-top: 20px; padding-top: 15px; border-top: 1px solid rgba(0,0,0,0.05); display: flex; justify-content: space-between; align-items: center;}

        /* yıldız oylama */
        .rating-form { display: flex; gap: 5px; }
        .rating-btn { background: rgba(255,255,255,0.8); border: 1px solid #e2e8f0; color: var(--text-muted); padding: 6px 12px; border-radius: 10px; font-size: 12px; cursor: pointer; transition: 0.2s; font-weight: 600;}
        .rating-btn.active, .rating-btn:hover { background: #fef3c7; border-color: #f59e0b; color: #d97706; transform: scale(1.05);}

        /* istatistikler */
        .stats-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 20px; margin-bottom: 40px; }
        .stat-card { padding: 24px; text-align: center; position: relative; overflow: hidden;}
        .stat-card::before { content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 5px; }
        .stat-su::before { background: var(--blue); }
        .stat-kalori::before { background: var(--orange); }
        .stat-uyku::before { background: var(--green); }
        .stat-spor::before { background: var(--purple); }
        .stat-kilo::before { background: var(--pink); }
        .stat-title { font-size: 13px; color: var(--text-muted); margin-bottom: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;}
        .stat-value { font-size: 26px; font-weight: 800; color: var(--text-main); }

        /* yeni veri giriş formu tasarımı */
        .entry-form-card { padding: 35px; margin-bottom: 40px;}
        
        .form-grid-inputs { display: grid; grid-template-columns: repeat(3, 1fr); gap: 25px; margin-bottom: 25px; }
        .form-grid-buttons { display: grid; grid-template-columns: repeat(2, 1fr); gap: 25px; margin-bottom: 30px; }

        .input-label { display: block; font-size: 12px; font-weight: 700; color: var(--text-muted); margin-bottom: 8px; margin-left: 5px; text-transform: uppercase; letter-spacing: 0.5px;}
        
        .input-icon-wrapper { position: relative; width: 100%; }
        .input-icon-wrapper i { position: absolute; left: 18px; top: 50%; transform: translateY(-50%); font-size: 18px; }
        .input-icon-wrapper .form-control { 
            padding-left: 50px; font-weight: 600; color: var(--text-main); height: 55px; 
            border-radius: 16px; border: 1px solid #e2e8f0; background: rgba(255,255,255,0.8); font-size: 15px; transition: 0.3s;
        }
        .input-icon-wrapper .form-control:focus { background: white; border-color: var(--blue); box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1); outline: none;}

        .btn-outline-custom { border-radius: 20px; padding: 15px; background: rgba(255,255,255,0.9); border: 2px solid transparent; transition: 0.3s; width: 100%; display: flex; align-items: center; cursor:pointer;}
        .btn-outline-custom:hover { background: white; transform: translateY(-3px); box-shadow: 0 10px 25px rgba(0,0,0,0.06); }
        .btn-meal { border-color: #dcfce7; }
        .btn-meal:hover { border-color: #34d399; box-shadow: 0 10px 25px rgba(16,185,129,0.15);}
        .btn-sport { border-color: #dbeafe; }
        .btn-sport:hover { border-color: #60a5fa; box-shadow: 0 10px 25px rgba(59,130,246,0.15);}
        
        .btn-submit { background: linear-gradient(135deg, var(--blue) 0%, #2563eb 100%); color: white; padding: 18px; border-radius: 18px; font-weight: 600; font-size:16px; width: 100%; border: none; transition: 0.3s; box-shadow: 0 10px 20px rgba(59, 130, 246, 0.2);}
        .btn-submit:hover { transform: translateY(-3px); box-shadow: 0 15px 30px rgba(59, 130, 246, 0.3); }

       
        .modal-content { border-radius: 24px; border: none; background: rgba(255,255,255,0.95); backdrop-filter: blur(20px); box-shadow: 0 25px 50px rgba(0,0,0,0.1);}
    </style>
</head>
<body>

<div id="particles-js"></div>

<div class="sidebar">
    <h2><i class="fas fa-heartbeat"></i> wellX </h2>
    
    <?php if ($is_premium): ?>
        <div onclick="document.getElementById('premiumModal').style.display='flex'" 
             style="background: linear-gradient(135deg, #fef3c7, #fde68a); padding: 8px 14px; border-radius: 12px; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 4px 10px rgba(245,158,11,0.15); color: #d97706; font-size: 12px; font-weight: bold; margin-bottom: 20px; cursor: pointer;" 
             title="Üyeliği Yönet">
            <i class="fas fa-crown"></i> PREMIUM
        </div>
    <?php endif; ?>
    
    <?php 
        $current_page = basename($_SERVER['PHP_SELF']); 
        if(!function_exists('isActive')){
            function isActive($page, $current) { return ($page == $current) ? 'active' : ''; }
        }
    ?>

    <nav style="flex-grow: 1;">
        <a href="panel.php" class="menu-item <?php echo isActive('panel.php', $current_page); ?>"><i class="fas fa-home"></i> Özet Paneli</a>
        <a href="beslenme.php" class="menu-item <?php echo isActive('beslenme.php', $current_page); ?>"><i class="fas fa-apple-alt"></i> Beslenme</a>
        <a href="egzersiz.php" class="menu-item <?php echo isActive('egzersiz.php', $current_page); ?>"><i class="fas fa-dumbbell"></i> Egzersiz</a>

        <a href="sorularim.php" class="menu-item <?php echo isActive('sorularim.php', $current_page); ?>">
            <i class="fas fa-envelope-open-text"></i> Uzmana Sorular
            <?php if($is_premium == 0): ?><i class="fas fa-lock ms-auto" style="font-size: 12px; color:#94a3b8;"></i><?php endif; ?>
        </a>

        <a href="gelisim.php" class="menu-item <?php echo isActive('gelisim.php', $current_page); ?>"><i class="fas fa-chart-line"></i> Gelişim</a>
        <a href="rozetlerim.php" class="menu-item <?php echo isActive('rozetlerim.php', $current_page); ?>"><i class="fas fa-medal"></i> Rozetlerim</a>
        <a href="turnuva.php" class="menu-item <?php echo isActive('turnuva.php', $current_page); ?>"><i class="fas fa-trophy"></i> Turnuva</a>
        <a href="profil.php" class="menu-item <?php echo isActive('profil.php', $current_page); ?>"><i class="fas fa-user"></i> Profil</a>
    </nav>
    
    <a href="cikis.php" class="menu-item logout-btn">
        <i class="fas fa-sign-out-alt"></i> Çıkış Yap
    </a>
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
            <div class="glass-card expert-card diet">
                <div class="expert-info">
                    <div class="expert-icon"><i class="fas fa-user-md"></i></div>
                    <div class="expert-details">
                        <h4>Diyetisyeniniz</h4>
                        <strong><?php echo htmlspecialchars($matched_diyetisyen['ad_soyad']); ?></strong>
                    </div>
                </div>
                <a href="uzman_secmesi.php?rol=diyetisyen" class="btn btn-outline-success btn-sm" style="border-radius:12px; font-weight:600;">Değiştir</a>
            </div>
        <?php else: ?>
            <div class="glass-card expert-card unassigned">
                <div class="expert-info">
                    <div class="expert-icon"><i class="fas fa-exclamation-circle"></i></div>
                    <div class="expert-details">
                        <h4>Diyetisyen</h4>
                        <strong>Henüz Seçilmedi</strong>
                    </div>
                </div>
                <a href="uzman_secmesi.php?rol=diyetisyen" class="btn btn-danger btn-sm" style="border-radius:12px; font-weight:600;">Seç</a>
            </div>
        <?php endif; ?>

        <?php if ($matched_hoca): ?>
            <div class="glass-card expert-card trainer">
                <div class="expert-info">
                    <div class="expert-icon"><i class="fas fa-running"></i></div>
                    <div class="expert-details">
                        <h4>Spor Hocanız</h4>
                        <strong><?php echo htmlspecialchars($matched_hoca['ad_soyad']); ?></strong>
                    </div>
                </div>
                <a href="uzman_secmesi.php?rol=hoca" class="btn btn-outline-primary btn-sm" style="border-radius:12px; font-weight:600;">Değiştir</a>
            </div>
        <?php else: ?>
            <div class="glass-card expert-card unassigned">
                <div class="expert-info">
                    <div class="expert-icon"><i class="fas fa-exclamation-circle"></i></div>
                    <div class="expert-details">
                        <h4>Spor Hocası</h4>
                        <strong>Henüz Seçilmedi</strong>
                    </div>
                </div>
                <a href="uzman_secmesi.php?rol=hoca" class="btn btn-danger btn-sm" style="border-radius:12px; font-weight:600;">Seç</a>
            </div>
        <?php endif; ?>
    </div>

    <div class="notifications-stack">
        <?php if ($yeni_beslenme): ?>
            <div class="glass-card alert-item alert-diet">
                <div><i class="fas fa-apple-alt me-2"></i> <strong style="font-weight: 500;">Diyetisyeniniz yeni bir beslenme planı paylaştı!</strong></div>
                <a href="beslenme.php" class="btn-alert">İncele</a>
            </div>
        <?php endif; ?>

        <?php if ($yeni_egzersiz): ?>
            <div class="glass-card alert-item alert-trainer">
                <div><i class="fas fa-dumbbell me-2"></i> <strong style="font-weight: 500;">Hocanız bugün için yeni bir egzersiz programı hazırladı!</strong></div>
                <a href="egzersiz.php" class="btn-alert">Programa Git</a>
            </div>
        <?php endif; ?>
    </div>

    <div class="daily-plan-grid">
        <div class="glass-card plan-card" style="border-left: 5px solid var(--blue);">
            <div class="plan-header">
                <h3 style="color: var(--blue);"><i class="fas fa-bullhorn"></i> Günün Antrenmanı</h3>
            </div>
            <?php if ($genel_antrenman): ?>
                <h5 style="color: var(--text-main); font-weight: 700; margin-bottom:10px;"><?php echo htmlspecialchars($genel_antrenman['antrenman_baslik']); ?></h5>
                <div class="plan-content"><?php echo nl2br(htmlspecialchars($genel_antrenman['antrenman_icerik'])); ?></div>
            <?php else: ?>
                <div class="plan-content" style="display:flex; align-items:center; justify-content:center; text-align:center; height:100%;">
                    Henüz günün antrenmanı paylaşılmadı.
                </div>
            <?php endif; ?>
        </div>

        <div class="glass-card plan-card" style="border-left: 5px solid var(--green);">
            <div class="plan-header">
                <h3 style="color: var(--green);"><i class="fas fa-utensils"></i> Günün Sağlıklı Tarifi</h3>
            </div>
            <?php if ($gunun_tarifi): ?>
                <h5 style="color: var(--text-main); font-weight: 700; margin-bottom:10px;"><?php echo htmlspecialchars($gunun_tarifi['tarif_baslik']); ?></h5>
                <div class="plan-content"><?php echo nl2br(htmlspecialchars($gunun_tarifi['tarif_icerik'])); ?></div>
                <div class="plan-footer">
                    <small style="color: var(--text-muted); font-weight:600;"><i class="fas fa-user-md me-1"></i> <?php echo htmlspecialchars($gunun_tarifi['ad_soyad']); ?></small>
                    <form action="islem_v2.php?is=puan_ver" method="POST" class="rating-form">
                        <input type="hidden" name="tarif_id" value="<?php echo $gunun_tarifi['id']; ?>">
                        <?php for($i=1; $i<=5; $i++): ?>
                            <button type="submit" name="puan" value="<?php echo $i; ?>" class="rating-btn <?php echo ($mevcut_puan == $i) ? 'active' : ''; ?>"><?php echo $i; ?> <i class="fas fa-star"></i></button>
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
        <div class="glass-card stat-card stat-su">
            <div class="stat-title">💧 İçilen Su</div>
            <div class="stat-value"><?php echo $su; ?> <span style="font-size:14px; color:var(--text-muted); font-weight:600;">/ 2.5 L</span></div>
        </div>
        <div class="glass-card stat-card stat-kalori">
            <div class="stat-title">🔥 Alınan Kalori</div>
            <div class="stat-value"><?php echo round($alinan); ?> <span style="font-size:14px; color:var(--text-muted); font-weight:600;">/ 2000</span></div>
        </div>
        <div class="glass-card stat-card stat-uyku">
            <div class="stat-title">😴 Uyku Süresi</div>
            <div class="stat-value"><?php echo $uyku; ?> <span style="font-size:14px; color:var(--text-muted); font-weight:600;">Saat</span></div>
        </div>
        <div class="glass-card stat-card stat-spor">
            <div class="stat-title">🏃 Spor Süresi</div>
            <div class="stat-value"><?php echo $spor; ?> <span style="font-size:14px; color:var(--text-muted); font-weight:600;">Dk</span></div>
        </div>
        <div class="glass-card stat-card stat-kilo">
            <div class="stat-title">⚖️ Güncel Kilo</div>
            <div class="stat-value"><?php echo $son_kilo; ?> <span style="font-size:14px; color:var(--text-muted); font-weight:600;">kg</span></div>
        </div>
    </div>

    <div class="glass-card entry-form-card">
        <div style="display:flex; align-items:center; gap:15px; margin-bottom: 30px;">
            <div style="background: linear-gradient(135deg, var(--blue), #2563eb); color: white; width: 45px; height: 45px; border-radius: 14px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 15px rgba(59,130,246,0.4);">
                <i class="fas fa-plus" style="font-size: 20px;"></i>
            </div>
            <h3 style="margin:0; font-weight:800; color: var(--text-main); letter-spacing:-0.5px;">Bugünün Verilerini Gir</h3>
        </div>
        
        <?php if ($mevcut_kayit): ?>
            <div class="alert alert-warning" style="border-radius:18px; border:none; background:rgba(254, 243, 199, 0.9); color:#d97706; display:flex; align-items:center; gap:12px; font-weight:600; margin-bottom: 30px; box-shadow: 0 4px 10px rgba(0,0,0,0.02);">
                <i class="fas fa-info-circle" style="font-size:22px;"></i> 
                <div>Bugün kayıt yaptınız. Yeni veriler eskilerin üzerine eklenerek günlüğünüze işlenecektir.</div>
            </div>
        <?php endif; ?>

        <form action="islem_v2.php?is=verileri_kaydet" method="POST">
            
            <div class="form-grid-inputs">
                <div>
                    <label class="input-label">İçilen Su (Litre)</label>
                    <div class="input-icon-wrapper">
                        <i class="fas fa-tint" style="color: #0ea5e9;"></i>
                        <input type="number" step="0.1" name="su_miktari" placeholder="Örn: 2.5" class="form-control">
                    </div>
                </div>
                <div>
                    <label class="input-label">Uyku (Saat)</label>
                    <div class="input-icon-wrapper">
                        <i class="fas fa-moon" style="color: #8b5cf6;"></i>
                        <input type="number" step="0.1" name="uyku_suresi" placeholder="Örn: 7.5" class="form-control">
                    </div>
                </div>
                <div>
                    <label class="input-label">Güncel Kilo (kg)</label>
                    <div class="input-icon-wrapper">
                        <i class="fas fa-weight" style="color: #ec4899;"></i>
                        <input type="number" step="0.1" name="guncel_kilo" placeholder="Örn: 65.5" value="<?php echo $son_kilo; ?>" class="form-control">
                    </div>
                </div>
            </div>

            <div class="form-grid-buttons">
                <button type="button" class="btn-outline-custom btn-meal" data-bs-toggle="modal" data-bs-target="#yemekModal">
                    <div style="background: #dcfce7; padding: 12px; border-radius: 14px; margin-right: 15px;"><i class="fas fa-apple-alt" style="font-size:22px; color: #166534;"></i></div>
                    <div style="text-align: left;">
                        <span style="display:block; font-weight:800; color: #166534; font-size: 16px;">Öğün Ekle</span>
                        <span style="font-size: 12px; color: #059669; font-weight: 600;">Yediklerini hesapla</span>
                    </div>
                    <i class="fas fa-chevron-right ms-auto" style="color: #a7f3d0; font-size: 20px;"></i>
                </button>

                <button type="button" class="btn-outline-custom btn-sport" data-bs-toggle="modal" data-bs-target="#egzersizModal">
                    <div style="background: #dbeafe; padding: 12px; border-radius: 14px; margin-right: 15px;"><i class="fas fa-running" style="font-size:22px; color: #1e40af;"></i></div>
                    <div style="text-align: left;">
                        <span style="display:block; font-weight:800; color: #1e40af; font-size: 16px;">Egzersiz Ekle</span>
                        <span style="font-size: 12px; color: #2563eb; font-weight: 600;">Yaktığını hesapla</span>
                    </div>
                    <i class="fas fa-chevron-right ms-auto" style="color: #bfdbfe; font-size: 20px;"></i>
                </button>
            </div>

            <input type="hidden" name="alinan_kalori" value="0">
            <input type="hidden" name="yakilan_kalori" value="0">
            <input type="hidden" name="spor_suresi" value="0">
            
            <button type="submit" class="btn-submit"><i class="fas fa-check-circle me-2"></i> Verileri Sisteme İşle</button>
        </form>
    </div>
</div>

<div class="modal fade" id="premiumInfoModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header" style="background: linear-gradient(135deg, #f59e0b, #d97706); color: white; border-radius: 24px 24px 0 0; border:none;">
        <h5 class="modal-title fw-bold"><i class="fas fa-crown me-2"></i> Neden Premium?</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4 text-center">
        <div style="font-size: 54px; color: #f59e0b; margin-bottom: 15px;"><i class="fas fa-star"></i></div>
        <p style="color: var(--text-main); font-size: 16px; font-weight:500; line-height:1.6;">Uzmanlarınızla birebir iletişim kurun, fotoğraf yükleyin ve size özel gelişim grafiklerine erişin.</p>
        <a href="premium_planlar.php" class="btn" style="background: var(--text-main); color:white; padding: 14px 28px; border-radius: 14px; text-decoration:none; display:inline-block; margin-top: 15px; font-weight:600; transition:0.3s;">Planları İncele</a>
      </div>
    </div>
  </div>
</div>   

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<?php if (isset($_GET['yeni_rozet'])): ?>
<script>
    Swal.fire({
        toast: true,
        position: 'top-end',
        icon: 'success',
        title: 'Tebrikler!',
        text: <?php echo json_encode($_GET['yeni_rozet'] . ' rozetini kazandın!'); ?>,
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true
    });
</script>
<?php endif; ?>

<div class="modal fade" id="yemekModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form action="islem_v2.php?is=yemek_ekle" method="POST">
        <div class="modal-header border-0 pb-0">
            <h5 class="modal-title fw-bold text-success"><i class="fas fa-apple-alt"></i> Ne Yedin?</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-4">
          <label class="fw-bold mb-2 small text-muted">Besin Seçimi</label>
          <select name="besin_adi" id="besinSec" class="form-select mb-3" onchange="hesaplaYemek()" style="border-radius: 14px; padding: 14px; background:#f8fafc;" required>
             <option value="" data-kal="0" data-birim="Adet/Kaşık">Listeden Seçin...</option>
             
             <optgroup label="Kahvaltılıklar">
                 <option value="Haşlanmış Yumurta" data-kal="75" data-birim="Adet">Haşlanmış Yumurta (1 Adet - 75 kcal)</option>
                 <option value="Zeytin" data-kal="5" data-birim="Adet">Zeytin (1 Adet - 5 kcal)</option>
                 <option value="Beyaz Peynir" data-kal="90" data-birim="İnce Dilim">Beyaz Peynir (1 İnce Dilim - 90 kcal)</option>
                 <option value="Tam Buğday Ekmek" data-kal="65" data-birim="Dilim">Tam Buğday Ekmek (1 Dilim - 65 kcal)</option>
                 <option value="Yulaf Ezmesi" data-kal="30" data-birim="Yemek Kaşığı">Yulaf Ezmesi (1 Yemek Kaşığı - 30 kcal)</option>
             </optgroup>

             <optgroup label="Ana Yemekler">
                 <option value="Izgara Tavuk" data-kal="165" data-birim="Porsiyon">Izgara Tavuk (1 Porsiyon - 165 kcal)</option>
                 <option value="Izgara Köfte" data-kal="60" data-birim="Adet">Izgara Köfte (1 Adet - 60 kcal)</option>
                 <option value="Pirinç Pilavı" data-kal="45" data-birim="Yemek Kaşığı">Pirinç Pilavı (1 Yemek Kaşığı - 45 kcal)</option>
                 <option value="Bulgur Pilavı" data-kal="35" data-birim="Yemek Kaşığı">Bulgur Pilavı (1 Yemek Kaşığı - 35 kcal)</option>
                 <option value="Mercimek Çorbası" data-kal="130" data-birim="Kase">Mercimek Çorbası (1 Kase - 130 kcal)</option>
                 <option value="Zeytinyağlı Sebze" data-kal="150" data-birim="Porsiyon">Zeytinyağlı Sebze Yemeği (1 Porsiyon - 150 kcal)</option>
             </optgroup>

             <optgroup label="Ara Öğün & Atıştırmalık">
                 <option value="Elma" data-kal="50" data-birim="Adet (Orta)">Elma (1 Adet Orta - 50 kcal)</option>
                 <option value="Muz" data-kal="90" data-birim="Adet (Orta)">Muz (1 Adet Orta - 90 kcal)</option>
                 <option value="Ceviz" data-kal="30" data-birim="Tam Adet">Ceviz (1 Tam Adet - 30 kcal)</option>
                 <option value="Çiğ Badem" data-kal="6" data-birim="Adet">Çiğ Badem (1 Adet - 6 kcal)</option>
                 <option value="Yoğurt" data-kal="20" data-birim="Yemek Kaşığı">Yoğurt (1 Yemek Kaşığı - 20 kcal)</option>
             </optgroup>
          </select>

          <label class="fw-bold mb-2 small text-muted">Miktar (<span id="birimGosterge" class="text-primary">Adet/Kaşık</span>)</label>
          <input type="number" name="miktar" id="yemekMiktar" class="form-control mb-3" placeholder="Örn: 2" oninput="hesaplaYemek()" style="border-radius: 14px; padding: 14px; background:#f8fafc;" required>
          
          <input type="hidden" name="toplam_kalori" id="gizliYemekKalori">
          <input type="hidden" name="birim" id="gizliBirim" value="Adet"> 
          
          <div class="p-3 mt-2 rounded-4 text-center" style="background: rgba(220, 252, 231, 0.6); border: 2px dashed #22c55e;">
              <span class="text-success fw-bold">Hesaplanan: <span id="gosterYemekKalori" class="fs-4">0</span> kcal</span>
          </div>
        </div>
        <div class="modal-footer border-0 pt-0">
          <button type="submit" class="btn btn-success w-100" style="border-radius: 14px; padding: 14px; font-weight: 600; box-shadow: 0 4px 15px rgba(34, 197, 94, 0.3);">Günlüğe Ekle</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="egzersizModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form action="islem_v2.php?is=egzersiz_ekle" method="POST">
        <div class="modal-header border-0 pb-0">
            <h5 class="modal-title fw-bold text-primary"><i class="fas fa-running"></i> Hangi Sporu Yaptın?</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-4">
          <label class="fw-bold mb-2 small text-muted">Egzersiz Seçimi</label>
          <select name="egzersiz_adi" id="sporSec" class="form-select mb-3" onchange="hesaplaSpor()" style="border-radius: 14px; padding: 14px; background:#f8fafc;" required>
             <option value="" data-kal="0">Listeden Seçin...</option>
             <option value="Tempolu Yürüyüş" data-kal="5">Tempolu Yürüyüş (1 dk = 5 kcal)</option>
             <option value="Koşu" data-kal="10">Koşu (1 dk = 10 kcal)</option>
             <option value="Pilates" data-kal="4">Pilates (1 dk = 4 kcal)</option>
             <option value="Ağırlık Antrenmanı" data-kal="6">Ağırlık Antrenmanı (1 dk = 6 kcal)</option>
             <option value="Bisiklet / Eliptik" data-kal="7">Bisiklet / Eliptik (1 dk = 7 kcal)</option>
             <option value="Yüzme" data-kal="8">Yüzme (1 dk = 8 kcal)</option>
          </select>

          <label class="fw-bold mb-2 small text-muted">Süre (Dakika)</label>
          <input type="number" name="sure_dk" id="sporSure" class="form-control mb-3" placeholder="Örn: 45" oninput="hesaplaSpor()" style="border-radius: 14px; padding: 14px; background:#f8fafc;" required>
          
          <input type="hidden" name="yakilan_kalori" id="gizliSporKalori">
          
          <div class="p-3 mt-2 rounded-4 text-center" style="background: rgba(219, 234, 254, 0.6); border: 2px dashed #3b82f6;">
              <span class="text-primary fw-bold">Yakılan: <span id="gosterSporKalori" class="fs-4">0</span> kcal</span>
          </div>
        </div>
        <div class="modal-footer border-0 pt-0">
          <button type="submit" class="btn btn-primary w-100" style="border-radius: 14px; padding: 14px; font-weight: 600; box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);">Günlüğe Ekle</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function hesaplaYemek() {
    let secim = document.getElementById('besinSec');
    let secilenOption = secim.options[secim.selectedIndex];
    let kaloriBirim = parseFloat(secilenOption.getAttribute('data-kal')) || 0;
    let birimTuru = secilenOption.getAttribute('data-birim') || 'Adet/Kaşık';
    let miktar = parseFloat(document.getElementById('yemekMiktar').value) || 0;
    let toplam = Math.round(kaloriBirim * miktar);
    
    document.getElementById('birimGosterge').innerText = birimTuru;
    document.getElementById('gosterYemekKalori').innerText = isNaN(toplam) ? 0 : toplam;
    document.getElementById('gizliYemekKalori').value = isNaN(toplam) ? 0 : toplam;
    document.getElementById('gizliBirim').value = birimTuru;
}

function hesaplaSpor() {
    let secim = document.getElementById('sporSec');
    let kalori1dk = secim.options[secim.selectedIndex].getAttribute('data-kal');
    let sure = document.getElementById('sporSure').value;
    let toplam = Math.round(kalori1dk * sure);
    let sonuc = isNaN(toplam) ? 0 : toplam;
    
    document.getElementById('gosterSporKalori').innerText = sonuc;
    document.getElementById('gizliSporKalori').value = sonuc;
}
</script>

<div id="premiumModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.4); backdrop-filter:blur(8px); z-index:10000; justify-content:center; align-items:center; font-family:'Poppins',sans-serif;">
    <div style="background:white; padding:40px 30px; border-radius:24px; text-align:center; max-width:380px; color:#333; box-shadow:0 25px 50px rgba(0,0,0,0.15);">
        <i class="fas fa-exclamation-triangle" style="font-size:50px; color:#ef4444; margin-bottom:20px;"></i>
        <h3 style="font-weight:800; margin-bottom:10px;">Premium İptali</h3>
        <p style="color:#64748b; font-size:14px; margin-bottom:25px;">Premium özelliklere erişiminizi kaybetmek istediğinize emin misiniz?</p>
        <div style="display:flex; flex-direction:column; gap:12px;">
            <a href="islem_v2.php?is=premium_iptal" style="background:#ef4444; color:white; text-decoration:none; padding:14px; border-radius:14px; font-weight:600; box-shadow:0 4px 15px rgba(239,68,68,0.3); transition:0.3s;">Evet, Üyeliğimi İptal Et</a>
            <button onclick="document.getElementById('premiumModal').style.display='none'" style="background:#f1f5f9; color:#475569; border:none; padding:14px; border-radius:14px; font-weight:600; cursor:pointer; transition:0.3s;">Vazgeç</button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/particles.js/2.0.0/particles.min.js"></script>
<script>
    particlesJS("particles-js", {
        "particles": {
            "number": { "value": 40, "density": { "enable": true, "value_area": 800 } },
            "color": { "value": "#3b82f6" },
            "shape": { "type": "circle" },
            "opacity": { "value": 0.2 },
            "size": { "value": 4 },
            "line_linked": { "enable": true, "distance": 150, "color": "#3b82f6", "opacity": 0.15, "width": 1 },
            "move": { "enable": true, "speed": 1.5 }
        },
        "interactivity": {
            "detect_on": "window",
            "events": { "onhover": { "enable": true, "mode": "grab" } }
        },
        "retina_detect": true
    });
</script>

</body>
</html>