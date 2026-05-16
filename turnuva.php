<?php
session_start();
include 'baglan.php';

if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit(); }

$user_id = $_SESSION['user_id'];

// premium kontrolü
$user_sorgu = $conn->prepare("SELECT is_premium FROM kullanicilar WHERE id = ?");
$user_sorgu->execute([$user_id]);
$is_premium = $user_sorgu->fetchColumn() ?? 0;

// turnuva verilerini çek
$sorgu = $conn->prepare("SELECT * FROM turnuva_puan_durumu LIMIT 15");
$sorgu->execute();
$siralamalar = $sorgu->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Turnuva | WellX</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        :root { 
            --blue: #3b82f6; --green: #10b981; 
            --text-main: #1e293b; --text-muted: #64748b;
            --glass-bg: rgba(255, 255, 255, 0.75);
            --glass-border: rgba(255, 255, 255, 0.6);
        }
        
        body { font-family: 'Poppins', sans-serif; margin: 0; display: flex; color: var(--text-main); background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 50%, #bae6fd 100%); background-attachment: fixed; min-height: 100vh; }
        #particles-js { position: fixed; width: 100%; height: 100%; top: 0; left: 0; z-index: 0; pointer-events: none; }
        
        
        .sidebar { width: 260px; height: 100vh; padding: 30px 20px; position: fixed; z-index: 100; background: rgba(255, 255, 255, 0.6); backdrop-filter: blur(20px); border-right: 1px solid var(--glass-border); box-shadow: 10px 0 30px rgba(0,0,0,0.03); display: flex; flex-direction: column; }
        .sidebar h2 { font-size: 28px; font-weight: 800; color: #111827; margin-bottom: 10px; letter-spacing: -1px; display: flex; align-items: center; gap: 10px;}
        .sidebar h2 i { color: #ef4444; filter: drop-shadow(0 0 8px rgba(239,68,68,0.4)); }
        
        .menu-item { display: flex; align-items: center; padding: 14px 18px; color: var(--text-muted); text-decoration: none; border-radius: 16px; margin-bottom: 8px; transition: all 0.3s ease; font-weight: 500; border: 1px solid transparent; }
        .menu-item i { transition: 0.3s; width: 25px; }
        .menu-item:hover { background: rgba(255, 255, 255, 0.9); color: var(--blue); transform: translateX(5px); box-shadow: 0 4px 15px rgba(59,130,246,0.05); }
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

       
        .main { margin-left: 260px; padding: 40px 50px; width: calc(100% - 260px); position: relative; z-index: 10; box-sizing: border-box;}
        
        .page-header { display: flex; align-items: center; gap: 15px; margin-bottom: 40px; }
        .header-icon { background: linear-gradient(135deg, #f97316, #ea580c); width: 50px; height: 50px; border-radius: 16px; display: flex; align-items: center; justify-content: center; color: white; font-size: 24px; box-shadow: 0 8px 20px rgba(249,115,22,0.3);}
        .page-header h1 { font-size: 32px; font-weight: 800; margin: 0; letter-spacing: -1px; color: #0f172a;}
        .page-header p { margin: 0; color: var(--text-muted); font-size: 15px; font-weight: 500;}

        
        .leaderboard-container { 
            background: var(--glass-bg); backdrop-filter: blur(15px); -webkit-backdrop-filter: blur(15px);
            border-radius: 24px; border: 1px solid var(--glass-border); padding: 35px; box-shadow: 0 15px 35px rgba(0,0,0,0.03); 
        }
        
        
        .user-row { 
            display: flex; align-items: center; padding: 15px 25px; margin-bottom: 12px; border-radius: 18px; 
            background: rgba(255,255,255,0.6); border: 1px solid rgba(255,255,255,0.9); transition: 0.3s;
        }
        .user-row:hover { transform: translateY(-3px) scale(1.01); box-shadow: 0 10px 20px rgba(0,0,0,0.04); background: white; }
        
        /* sen vurgusu tasarımı */
        .current-user { 
            background: rgba(219, 234, 254, 0.6) !important; 
            border: 2px solid var(--blue) !important; 
            box-shadow: 0 8px 20px rgba(59,130,246,0.15) !important; 
        }

        
        .rank-circle {
            width: 45px; height: 45px; border-radius: 50%; display: flex; align-items: center; justify-content: center; 
            font-weight: 800; margin-right: 20px; font-size: 16px; flex-shrink: 0;
        }
        
        .first { background: linear-gradient(135deg, #fcd34d, #d97706); color: white; box-shadow: 0 5px 15px rgba(245,158,11,0.3); border:none; }
        .second { background: linear-gradient(135deg, #cbd5e1, #64748b); color: white; box-shadow: 0 5px 15px rgba(148,163,184,0.3); border:none; }
        .third { background: linear-gradient(135deg, #fdba74, #ea580c); color: white; box-shadow: 0 5px 15px rgba(234,88,12,0.3); border:none; }
        .others { background: rgba(255,255,255,0.8); color: var(--text-muted); border: 1px solid #e2e8f0; }

        /* xp rozeti tasarımı */
        .xp-badge { 
            background: linear-gradient(135deg, var(--blue), #2563eb); color: white; 
            padding: 10px 20px; border-radius: 14px; font-weight: 700; font-size: 15px; 
            box-shadow: 0 5px 15px rgba(59,130,246,0.25); letter-spacing: 0.5px;
        }
    </style>
</head>
<body>

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
        if(!function_exists('isActive')){ function isActive($page, $current) { return ($page == $current) ? 'active' : ''; } }
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

<div class="main">
    <div class="page-header">
        <div class="header-icon"><i class="fas fa-trophy"></i></div>
        <div>
            <h1>Turnuva Sıralaması</h1>
            <p>Su, Spor, Uyku ve Düzenli Veri Girişi ile XP (Puan) toplayın, zirveye yerleşin!</p>
        </div>
    </div>

    <div class="leaderboard-container">
        <?php foreach ($siralamalar as $index => $row): 
            $rank = $index + 1;
            $rankClass = 'others';
            if($rank == 1) $rankClass = 'first';
            elseif($rank == 2) $rankClass = 'second';
            elseif($rank == 3) $rankClass = 'third';
            
            $is_me = ($row['id'] == $user_id);
        ?>
            <div class="user-row <?php echo $is_me ? 'current-user' : ''; ?>">
                <div class="rank-circle <?php echo $rankClass; ?>">
                    <?php echo $rank; ?>
                </div>
                
                <div style="flex-grow: 1;">
                    <span style="font-weight: 700; font-size: 16px; color: #1e293b; display:flex; align-items:center; gap:8px;">
                        <?php echo htmlspecialchars($row['ad_soyad']); ?>
                        <?php if($is_me) echo '<span class="badge" style="background:#3b82f6; color:white; font-size:10px; padding:4px 8px; border-radius:8px;">SEN</span>'; ?>
                        <?php if($rank == 1) echo '<i class="fas fa-crown" style="color:#f59e0b; font-size:14px;"></i>'; ?>
                    </span>
                    <div style="font-size: 13px; color: #64748b; margin-top:3px; font-weight:500;">
                        <i class="fas fa-check-circle" style="color:#10b981;"></i> <?php echo $row['kayit_sayisi']; ?> Günlük Kayıt
                    </div>
                </div>

                <div class="xp-badge">
                    <?php echo number_format($row['toplam_puan'], 0, ',', '.'); ?> XP
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/particles.js/2.0.0/particles.min.js"></script>
<script>
    particlesJS("particles-js", { "particles": { "number": { "value": 40 }, "color": { "value": "#3b82f6" }, "opacity": { "value": 0.2 }, "size": { "value": 4 }, "line_linked": { "enable": true, "color": "#3b82f6", "opacity": 0.15 }, "move": { "enable": true, "speed": 1.5 } } });
</script>
</body>
</html>