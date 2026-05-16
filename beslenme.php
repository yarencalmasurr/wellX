<?php
session_start();
include 'baglan.php';

// oturum kontrolü
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// kullanıcı bilgilerini çek
$user_sorgu = $conn->prepare("SELECT * FROM kullanicilar WHERE id = ?");
$user_sorgu->execute([$user_id]);
$user_data = $user_sorgu->fetch(PDO::FETCH_ASSOC);
$is_premium = $user_data['is_premium'] ?? 0;

// bildirimi okundu yap yani sayfaya girince bildirim silinir
try {
    $update = $conn->prepare("UPDATE beslenme_planlari SET okundu = 1 WHERE user_id = ? AND DATE(kayit_tarihi) = CURDATE()");
    $update->execute([$user_id]);
} catch (PDOException $e) {}

// bugünün planlarını çek
$sorgu = $conn->prepare("SELECT bp.*, k.ad_soyad as diyetisyen_adi 
                         FROM beslenme_planlari bp 
                         LEFT JOIN kullanicilar k ON bp.diyetisyen_id = k.id 
                         WHERE bp.user_id = ? 
                         AND DATE(bp.kayit_tarihi) = CURDATE()
                         ORDER BY bp.kayit_tarihi DESC");
$sorgu->execute([$user_id]);
$planlar = $sorgu->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Beslenme Planım | WellX</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        :root { 
            --blue: #3b82f6; --green: #10b981;
            --text-main: #1e293b; --text-muted: #64748b;
            --glass-bg: rgba(255, 255, 255, 0.75);
            --glass-border: rgba(255, 255, 255, 0.6);
        }
        
        body { 
            font-family: 'Poppins', sans-serif; margin: 0; display: flex; color: var(--text-main); 
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 50%, #bae6fd 100%);
            background-attachment: fixed; min-height: 100vh;
        }

        #particles-js { position: fixed; width: 100%; height: 100%; top: 0; left: 0; z-index: 0; pointer-events: none; }
        
        /* sidebar tasarımı  */
        .sidebar { 
            width: 260px; height: 100vh; padding: 30px 20px; position: fixed; z-index: 100;
            background: rgba(255, 255, 255, 0.6); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);
            border-right: 1px solid var(--glass-border); box-shadow: 10px 0 30px rgba(0,0,0,0.03);
            display: flex; flex-direction: column;
        }
        
        .sidebar h2 { font-size: 28px; font-weight: 800; color: #111827; margin-bottom: 10px; letter-spacing: -1px; display: flex; align-items: center; gap: 10px;}
        .sidebar h2 i { color: #ef4444; filter: drop-shadow(0 0 8px rgba(239,68,68,0.4)); }

        .menu-item { display: flex; align-items: center; padding: 14px 18px; color: var(--text-muted); text-decoration: none; border-radius: 16px; margin-bottom: 8px; transition: all 0.3s ease; font-weight: 500; border: 1px solid transparent; }
        .menu-item i { transition: 0.3s; width: 25px; }
        .menu-item:hover { background: rgba(255, 255, 255, 0.9); color: var(--blue); transform: translateX(5px); border-color: rgba(255,255,255,0.8); box-shadow: 0 4px 15px rgba(59,130,246,0.05); }
        .menu-item.active { background: linear-gradient(135deg, #dbeafe, #eff6ff); color: var(--blue); font-weight: 700; box-shadow: 0 8px 20px rgba(59,130,246,0.1); border-color: white; }

        .menu-item:nth-of-type(1) i { color: #3b82f6; }
        .menu-item:nth-of-type(2) i { color: #10b981; }
        .menu-item:nth-of-type(3) i { color: #8b5cf6; }
        .menu-item:nth-of-type(4) i { color: #f59e0b; }
        .menu-item:nth-of-type(5) i { color: #06b6d4; }
        .menu-item:nth-of-type(6) i { color: #ec4899; }
        .menu-item:nth-of-type(7) i { color: #f97316; }
        .menu-item:nth-of-type(8) i { color: #6366f1; }
        
        .sidebar .logout-btn { margin-top: auto !important; background: rgba(254, 226, 226, 0.6); color: #ef4444 !important; font-weight: 600; }
        .sidebar .logout-btn:hover { background: #fee2e2; color: #dc2626 !important; transform: translateX(0); }

        /* ana içerik */
        .content { margin-left: 260px; padding: 40px 50px; width: calc(100% - 260px); position: relative; z-index: 10; box-sizing: border-box;}
        
        .page-header { display: flex; align-items: center; gap: 15px; margin-bottom: 40px; }
        .header-icon { background: linear-gradient(135deg, #10b981, #059669); width: 50px; height: 50px; border-radius: 16px; display: flex; align-items: center; justify-content: center; color: white; font-size: 24px; box-shadow: 0 8px 20px rgba(16,185,129,0.3);}
        .page-header h1 { font-size: 32px; font-weight: 800; margin: 0; letter-spacing: -1px; color: #0f172a;}

        /* cam görünüm */
        .glass-card {
            background: var(--glass-bg); backdrop-filter: blur(15px); -webkit-backdrop-filter: blur(15px);
            border-radius: 24px; border: 1px solid var(--glass-border);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.03); padding: 35px; margin-bottom: 30px;
            transition: 0.3s ease; position: relative; overflow: hidden;
        }
        .glass-card::before { content: ''; position: absolute; top: 0; left: 0; width: 6px; height: 100%; background: var(--green); }
        .glass-card:hover { transform: translateY(-5px); box-shadow: 0 20px 40px rgba(0, 0, 0, 0.06); }

        .plan-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px dashed rgba(0,0,0,0.06); padding-bottom: 15px; margin-bottom: 20px; }
        .plan-header h3 { margin: 0; font-size: 18px; font-weight: 700; color: #0f172a; display: flex; align-items: center; gap: 10px;}
        .plan-date { font-size: 13px; color: #64748b; font-weight: 600; background: rgba(255,255,255,0.8); padding: 6px 14px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.02);}
        
        .plan-text { white-space: pre-line; color: #334155; line-height: 1.8; font-size: 15px; background: rgba(255,255,255,0.5); padding: 20px; border-radius: 16px;}
        
        .no-plan { text-align: center; padding: 60px 20px; background: var(--glass-bg); backdrop-filter: blur(15px); border-radius: 24px; border: 1px solid var(--glass-border); box-shadow: 0 15px 35px rgba(0,0,0,0.03);}
        .no-plan i { font-size: 60px; color: #cbd5e1; margin-bottom: 20px;}
        .no-plan h3 { color: #1e293b; font-weight: 700; margin-bottom: 10px;}
        .no-plan p { color: #64748b; margin: 0;}
    </style>
</head>
<body>
 <!-- beslenme planlarının listelendiği kullanıcı arayüzü ve sidebar sistemi -->

<div id="particles-js"></div>

<div class="sidebar">
    <h2><i class="fas fa-heartbeat"></i> wellX </h2>
    
    <?php if ($is_premium): ?>
        <div style="background: linear-gradient(135deg, #fef3c7, #fde68a); padding: 8px 14px; border-radius: 12px; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 4px 10px rgba(245,158,11,0.15); color: #d97706; font-size: 12px; font-weight: bold; margin-bottom: 20px;">
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
        <a href="sorularim.php" class="menu-item <?php echo isActive('sorularim.php', $current_page); ?>"><i class="fas fa-envelope-open-text"></i> Uzmana Sorular</a>
        <a href="gelisim.php" class="menu-item <?php echo isActive('gelisim.php', $current_page); ?>"><i class="fas fa-chart-line"></i> Gelişim</a>
        <a href="rozetlerim.php" class="menu-item <?php echo isActive('rozetlerim.php', $current_page); ?>"><i class="fas fa-medal"></i> Rozetlerim</a>
        <a href="turnuva.php" class="menu-item <?php echo isActive('turnuva.php', $current_page); ?>"><i class="fas fa-trophy"></i> Turnuva</a>
        <a href="profil.php" class="menu-item <?php echo isActive('profil.php', $current_page); ?>"><i class="fas fa-user"></i> Profil</a>
    </nav>
    
    <a href="cikis.php" class="menu-item logout-btn"><i class="fas fa-sign-out-alt"></i> Çıkış Yap</a>
</div>

<div class="content">
    <div class="page-header">
        <div class="header-icon"><i class="fas fa-leaf"></i></div>
        <h1>Bugünün Beslenme Planları</h1>
    </div>

    <?php if (count($planlar) > 0): ?>
        <?php foreach ($planlar as $plan): ?>
            <div class="glass-card">
                <div class="plan-header">
                    <h3><i class="fas fa-user-md" style="color:var(--green)"></i> Diyetisyen: <?php echo htmlspecialchars($plan['diyetisyen_adi']); ?></h3>
                    <span class="plan-date"><i class="far fa-calendar-alt"></i> <?php echo date('d.m.Y H:i', strtotime($plan['kayit_tarihi'])); ?></span>
                </div>
                <div class="plan-text">
                    <?php echo nl2br(htmlspecialchars($plan['plan_metni'] ?? '')); ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="no-plan">
            <i class="fas fa-utensils"></i>
            <h3>Henüz bugüne ait bir planınız bulunmuyor.</h3>
            <p>Diyetisyeniniz yeni bir plan paylaştığında burada anlık olarak göreceksiniz.</p>
        </div>
    <?php endif; ?>
</div>
<!-- arkaplan animasyon efekti -->
<script src="https://cdn.jsdelivr.net/particles.js/2.0.0/particles.min.js"></script>
<script src="https://cdn.jsdelivr.net/particles.js/2.0.0/particles.min.js"></script>
<script>
    particlesJS("particles-js", {
        "particles": { "number": { "value": 40 }, "color": { "value": "#3b82f6" }, "opacity": { "value": 0.2 }, "size": { "value": 4 }, "line_linked": { "enable": true, "color": "#3b82f6", "opacity": 0.15 }, "move": { "enable": true, "speed": 1.5 } }
    });
</script>
</body>
</html>