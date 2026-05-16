<?php
session_start();
include 'baglan.php';

if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit(); }
$user_id = $_SESSION['user_id'];

//premium kontrolü
$user_sorgu = $conn->prepare("SELECT is_premium FROM kullanicilar WHERE id = ?");
$user_sorgu->execute([$user_id]);
$is_premium = $user_sorgu->fetchColumn() ?? 0;

// kullanıcının rozetlerini çek
$sorgu = $conn->prepare("
    SELECT r.*, kr.kazanma_tarihi 
    FROM rozetler r 
    JOIN kullanici_rozetleri kr ON r.id = kr.rozet_id 
    WHERE kr.user_id = ? 
    ORDER BY kr.kazanma_tarihi DESC
");
$sorgu->execute([$user_id]);
$rozetler = $sorgu->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rozetlerim | WellX</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
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
        .sidebar h2 i { color: #ef4444; }
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
        .header-icon { background: linear-gradient(135deg, #f59e0b, #d97706); width: 50px; height: 50px; border-radius: 16px; display: flex; align-items: center; justify-content: center; color: white; font-size: 24px; box-shadow: 0 8px 20px rgba(245,158,11,0.3);}
        .page-header h1 { font-size: 32px; font-weight: 800; margin: 0; letter-spacing: -1px; color: #0f172a;}

        .badge-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 25px; }
        
        .badge-card { 
            background: var(--glass-bg); backdrop-filter: blur(15px); border-radius: 24px; border: 1px solid var(--glass-border); 
            padding: 30px 20px; text-align: center; box-shadow: 0 15px 35px rgba(0,0,0,0.03); 
            transition: 0.3s; position: relative; overflow: hidden;
        }
        .badge-card::before { content:''; position:absolute; top:0; left:0; width:100%; height:5px; background: linear-gradient(90deg, #f59e0b, #fcd34d); }
        .badge-card:hover { transform: translateY(-5px); box-shadow: 0 20px 40px rgba(0,0,0,0.06); }
        
        .badge-icon { font-size: 50px; margin-bottom: 15px; display: block; filter: drop-shadow(0 5px 10px rgba(0,0,0,0.1)); transition: 0.3s;}
        .badge-card:hover .badge-icon { transform: scale(1.1); }
        
        .badge-card h3 { margin: 0 0 5px 0; font-size: 16px; font-weight: 700; color: #1e293b; }
        .badge-card p { font-size: 12px; color: var(--text-muted); margin: 0 0 15px 0; font-weight: 500;}
        .badge-date { font-size: 11px; color: #94a3b8; font-weight: 600; background: rgba(255,255,255,0.8); padding: 4px 10px; border-radius: 8px; }

        .empty-state { text-align: center; padding: 60px 20px; background: var(--glass-bg); backdrop-filter: blur(15px); border-radius: 24px; border: 1px dashed #cbd5e1; grid-column: 1 / -1;}
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
        <div class="header-icon"><i class="fas fa-award"></i></div>
        <h1>Başarı Rozetlerim</h1>
    </div>

    <div class="badge-grid">
        <?php if(count($rozetler) > 0): ?>
            <?php foreach($rozetler as $r): ?>
                <div class="badge-card">
                    <span class="badge-icon">
                        <?php 
                        if($r['kategori'] == 'su') echo "💧";
                        elseif($r['kategori'] == 'uyku') echo "🌙";
                        else echo "🏃";
                        ?>
                    </span>
                    <h3><?php echo htmlspecialchars($r['rozet_adi']); ?></h3>
                    <p><?php echo htmlspecialchars($r['rozet_aciklamasi']); ?></p>
                    <span class="badge-date"><i class="fas fa-calendar-check me-1"></i> <?php echo date('d.m.Y', strtotime($r['kazanma_tarihi'])); ?></span>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-medal" style="font-size: 50px; color: #cbd5e1; margin-bottom:15px;"></i>
                <h4 style="color:#475569; font-weight:700;">Henüz rozet kazanmadınız</h4>
                <p style="color:#94a3b8; margin:0;">Su içerek, uyuyarak ve spor yaparak yeni rozetlerin kilitlerini açın!</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/particles.js/2.0.0/particles.min.js"></script>
<script>
    particlesJS("particles-js", { "particles": { "number": { "value": 40 }, "color": { "value": "#3b82f6" }, "opacity": { "value": 0.2 }, "size": { "value": 4 }, "line_linked": { "enable": true, "color": "#3b82f6", "opacity": 0.15 }, "move": { "enable": true, "speed": 1.5 } } });
</script>
</body>
</html>